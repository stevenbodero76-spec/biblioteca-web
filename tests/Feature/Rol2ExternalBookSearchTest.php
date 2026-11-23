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
        $service = new BookLinkInterconnectorService();

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

        $service = new BookLinkInterconnectorService();
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
        $categoria = Category::create(['nombre' => 'Clásicos Universales', 'icono' => '🏛️', 'slug' => 'clasicos']);

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
}
