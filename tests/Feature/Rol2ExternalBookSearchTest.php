<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\BookLinkInterconnectorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Rol2ExternalBookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_interconnector_converts_archive_org_details_to_embed_url(): void
    {
        $service = new BookLinkInterconnectorService;

        $embed = $service->convertirAEmbedUrl('https://archive.org/details/donquijote00cerv');
        $this->assertSame('https://archive.org/embed/donquijote00cerv', $embed);

        $directEmbed = $service->convertirAEmbedUrl('https://archive.org/embed/donquijote00cerv');
        $this->assertSame('https://archive.org/embed/donquijote00cerv', $directEmbed);
    }

    public function test_external_search_queries_apis_and_returns_normalized_books(): void
    {
        Http::fake([
            'openlibrary.org/search.json*' => Http::response([
                'docs' => [
                    [
                        'title' => 'Don Quijote de la Mancha',
                        'author_name' => ['Miguel de Cervantes'],
                        'first_publish_year' => 1605,
                        'cover_i' => 12345,
                        'ia' => ['donquijote00cerv'],
                        'publisher' => ['Francisco de Robles'],
                    ],
                ],
            ], 200),
            'gutendex.com/books*' => Http::response([
                'results' => [
                    [
                        'id' => 2000,
                        'title' => 'El ingenioso hidalgo Don Quijote de la Mancha',
                        'authors' => [['name' => 'Cervantes Saavedra, Miguel de']],
                        'formats' => [
                            'text/html' => 'https://www.gutenberg.org/ebooks/2000.html.images',
                            'image/jpeg' => 'https://www.gutenberg.org/cache/epub/2000/pg2000.cover.medium.jpg',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new BookLinkInterconnectorService;
        $resultados = $service->buscarEnRedesExternas('Don Quijote', 2);

        $this->assertNotEmpty($resultados);
        $this->assertCount(2, $resultados);

        $this->assertSame('Don Quijote de la Mancha', $resultados[0]['titulo']);
        $this->assertSame('https://archive.org/embed/donquijote00cerv', $resultados[0]['archivo_pdf']);
        $this->assertTrue($resultados[0]['es_embebible']);

        $this->assertSame('El ingenioso hidalgo Don Quijote de la Mancha', $resultados[1]['titulo']);
        $this->assertSame('Project Gutenberg', $resultados[1]['fuente']);
    }

    public function test_catalog_index_returns_external_results_when_local_catalog_has_no_matches(): void
    {
        Http::fake([
            'openlibrary.org/search.json*' => Http::response([
                'docs' => [
                    [
                        'title' => 'Dracula',
                        'author_name' => ['Bram Stoker'],
                        'first_publish_year' => 1897,
                        'ia' => ['dracula00stok'],
                    ],
                ],
            ], 200),
            'gutendex.com/books*' => Http::response(['results' => []], 200),
        ]);

        $response = $this->get('/books?buscar=DraculaInexistenteEnLocal');

        $response->assertStatus(200);
        $response->assertSee('Dracula');
        $response->assertSee('Red Global Abierta');
        $response->assertSee('Leer Gratis en Línea');
    }

    public function test_admin_can_import_external_book_in_one_click(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);
        $categoria = Category::create(['nombre' => 'Clásicos Universales', 'icono' => '🏛️']);

        $response = $this->actingAs($admin)->post('/books/importar-externo', [
            'titulo' => 'Sherlock Holmes: Estudio en Escarlata',
            'autor' => 'Arthur Conan Doyle',
            'editorial' => 'Ward Lock & Co',
            'anio_publicacion' => 1887,
            'archivo_pdf' => 'https://archive.org/embed/astudyinscarlet00doyl',
            'category_id' => $categoria->id,
        ]);

        $this->assertDatabaseHas('books', [
            'titulo' => 'Sherlock Holmes: Estudio en Escarlata',
            'autor' => 'Arthur Conan Doyle',
            'archivo_pdf' => 'https://archive.org/embed/astudyinscarlet00doyl',
            'licencia' => 'dominio_publico',
        ]);

        $nuevoLibro = Book::where('titulo', 'Sherlock Holmes: Estudio en Escarlata')->first();
        $response->assertRedirect(route('books.show', $nuevoLibro));
    }

    public function test_regular_user_cannot_import_external_book(): void
    {
        $cliente = User::factory()->create(['rol' => 'cliente']);

        $response = $this->actingAs($cliente)->post('/books/importar-externo', [
            'titulo' => 'Intento No Autorizado',
            'autor' => 'Hacker',
            'archivo_pdf' => 'https://example.com/fake.pdf',
        ]);

        $response->assertRedirect(route('home'));
    }

    public function test_interconnector_resolves_openlibrary_urls_to_archive_embed_and_renders_iframe(): void
    {
        Http::fake([
            'openlibrary.org/api/books*' => Http::response([
                'ISBN:9780451524935' => [
                    'title' => 'Nineteen Eighty-Four',
                    'identifiers' => [
                        'internet_archive' => ['nineteeneightyfo00orwe_8'],
                    ],
                ],
            ], 200),
            'openlibrary.org/books/OL7353617M.json' => Http::response([
                'ocaid' => 'fantasticmrfox00dahl_0',
            ], 200),
            'openlibrary.org/works/OL1168083W/editions.json*' => Http::response([
                'entries' => [
                    ['title' => 'Edición sin scan'],
                    ['title' => 'Edición digitalizada', 'ocaid' => 'mobydickorwhale00melv'],
                ],
            ], 200),
        ]);

        $service = new BookLinkInterconnectorService;

        $this->assertSame(
            'https://archive.org/embed/nineteeneightyfo00orwe_8',
            $service->convertirAEmbedUrl('https://openlibrary.org/isbn/9780451524935')
        );
        $this->assertSame(
            'https://archive.org/embed/fantasticmrfox00dahl_0',
            $service->convertirAEmbedUrl('https://openlibrary.org/books/OL7353617M/Fantastic_Mr_Fox')
        );
        $this->assertSame(
            'https://archive.org/embed/mobydickorwhale00melv',
            $service->convertirAEmbedUrl('https://openlibrary.org/works/OL1168083W')
        );

        $categoria = Category::create(['nombre' => 'Distopía', 'icono' => '📖']);
        $book = Book::create([
            'category_id' => $categoria->id,
            'isbn' => '978-0451524935',
            'titulo' => '1984 (Open Library Link)',
            'autor' => 'George Orwell',
            'formato' => 'digital',
            'idioma' => 'en',
            'licencia' => 'dominio_publico',
            'archivo_pdf' => 'https://openlibrary.org/isbn/9780451524935',
            'stock_total' => 5,
            'stock_disponible' => 5,
        ]);

        // Visitante sin sesión no debe ver el iframe embebido ni acceder directo a la Sala de Lectura
        $this->get(route('books.read', $book))->assertRedirect(route('login'));
        $this->get(route('books.show', $book))
            ->assertStatus(200)
            ->assertDontSee('<iframe', false);

        $lector = User::factory()->create(['rol' => 'cliente']);

        $response = $this->actingAs($lector)->get(route('books.show', $book));
        $response->assertStatus(200);
        $response->assertSee('https://archive.org/embed/nineteeneightyfo00orwe_8');
        $response->assertSee('<iframe', false);

        $this->actingAs($lector)
            ->get(route('books.read', $book))
            ->assertStatus(200)
            ->assertSee('https://archive.org/embed/nineteeneightyfo00orwe_8')
            ->assertSee('<iframe', false);

        // Los códigos internos no ISBN (ej. PD-LIT-1605) no deben generar /isbn/1605 inválido
        $bookPd = Book::create([
            'category_id' => $categoria->id,
            'isbn' => 'PD-LIT-1605',
            'titulo' => 'Don Quijote',
            'autor' => 'Miguel de Cervantes',
            'formato' => 'digital',
            'archivo_pdf' => 'https://archive.org/embed/elingeniosohidal00cervuoft',
            'stock_total' => 2,
            'stock_disponible' => 2,
        ]);
        $enlacesPd = $service->resolverEnlacesLectura($bookPd, false);
        $this->assertStringStartsWith('https://openlibrary.org/search?q=', $enlacesPd['fuentes'][0]['url']);
        $this->assertSame('https://archive.org/details/elingeniosohidal00cervuoft', $enlacesPd['fuentes'][1]['url']);
    }

    public function test_catalog_and_api_filter_books_by_idioma_and_licencia(): void
    {
        $categoria = Category::create(['nombre' => 'Idiomas', 'icono' => '🌍']);

        $libroIngles = Book::create([
            'category_id' => $categoria->id,
            'isbn' => '978-0000000101',
            'titulo' => 'Public Domain English Classic',
            'autor' => 'Charles Dickens',
            'formato' => 'digital',
            'idioma' => 'en',
            'licencia' => 'dominio_publico',
            'stock_total' => 3,
            'stock_disponible' => 3,
        ]);

        $libroEspanol = Book::create([
            'category_id' => $categoria->id,
            'isbn' => '978-0000000102',
            'titulo' => 'Obra Moderna en Español',
            'autor' => 'Autor Actual',
            'formato' => 'digital',
            'idioma' => 'es',
            'licencia' => 'acceso_abierto',
            'stock_total' => 3,
            'stock_disponible' => 3,
        ]);

        $webRes = $this->get('/books?idioma=en&licencia=dominio_publico');
        $webRes->assertStatus(200)
            ->assertViewHas('books', function ($books) use ($libroIngles, $libroEspanol) {
                $ids = $books->pluck('id')->all();

                return in_array($libroIngles->id, $ids, true)
                    && ! in_array($libroEspanol->id, $ids, true);
            });

        $partialRes = $this->getJson('/books?partial=1&idioma=en&licencia=dominio_publico');
        $partialRes->assertStatus(200)
            ->assertJsonPath('total', 1);
        $this->assertStringContainsString($libroIngles->titulo, $partialRes->json('html'));
        $this->assertStringNotContainsString($libroEspanol->titulo, $partialRes->json('html'));

        $apiRes = $this->getJson('/api/v1/books?idioma=en&licencia=dominio_publico');
        $apiRes->assertStatus(200)
            ->assertJsonFragment(['titulo' => $libroIngles->titulo])
            ->assertJsonMissing(['titulo' => $libroEspanol->titulo]);
    }
}
