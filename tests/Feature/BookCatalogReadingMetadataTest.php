<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Reader;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCatalogReadingMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_language_and_license_metadata_for_a_book(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $category = Category::firstOrFail();

        $this->actingAs($admin)
            ->post(route('books.store'), [
                'category_id' => $category->id,
                'isbn' => '9799000000001',
                'titulo' => 'Lectura abierta de prueba',
                'autor' => 'Autora de prueba',
                'formato' => 'digital',
                'idioma' => 'es',
                'licencia' => 'dominio_publico',
                'stock_total' => 1,
                'stock_disponible' => 1,
            ])
            ->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'isbn' => '9799000000001',
            'idioma' => 'es',
            'licencia' => 'dominio_publico',
        ]);
    }

    public function test_admin_cannot_save_unsupported_language_or_license_values(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $category = Category::firstOrFail();

        $this->actingAs($admin)
            ->post(route('books.store'), [
                'category_id' => $category->id,
                'isbn' => '9799000000002',
                'titulo' => 'Lectura con metadatos inválidos',
                'autor' => 'Autora de prueba',
                'formato' => 'digital',
                'idioma' => 'fr',
                'licencia' => 'copyright',
                'stock_total' => 1,
                'stock_disponible' => 1,
            ])
            ->assertSessionHasErrors(['idioma', 'licencia']);

        $this->assertDatabaseMissing('books', ['isbn' => '9799000000002']);
    }

    public function test_catalog_filters_books_by_language_and_license_together(): void
    {
        $this->seed(DatabaseSeeder::class);
        $category = Category::create([
            'nombre' => 'Metadatos de lectura de prueba',
            'icono' => '📚',
        ]);

        $spanishPublicDomainBook = Book::create([
            'category_id' => $category->id,
            'isbn' => '9799000000003',
            'titulo' => 'Resultado español de dominio público',
            'autor' => 'Autora de prueba',
            'formato' => 'digital',
            'idioma' => 'es',
            'licencia' => 'dominio_publico',
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);

        Book::create([
            'category_id' => $category->id,
            'isbn' => '9799000000004',
            'titulo' => 'Resultado inglés de acceso abierto',
            'autor' => 'Author for test',
            'formato' => 'digital',
            'idioma' => 'en',
            'licencia' => 'acceso_abierto',
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);

        $this->get(route('books.index', [
            'category_id' => $category->id,
            'idioma' => 'es',
            'licencia' => 'dominio_publico',
        ]))
            ->assertOk()
            ->assertSee($spanishPublicDomainBook->titulo)
            ->assertSee('📖 Leer / Abrir')
            ->assertSee('Open Library')
            ->assertSee('Internet Archive')
            ->assertSee('MangaPlus')
            ->assertDontSee('Resultado inglés de acceso abierto');
    }

    public function test_book_detail_displays_open_reading_sources_and_metadata_badges(): void
    {
        $this->seed(DatabaseSeeder::class);
        $category = Category::create([
            'nombre' => 'Lectura abierta de prueba',
            'icono' => '📖',
        ]);
        $book = Book::create([
            'category_id' => $category->id,
            'isbn' => '9799000000005',
            'titulo' => 'Obra con fuentes abiertas',
            'autor' => 'Autora de prueba',
            'formato' => 'digital',
            'idioma' => 'es',
            'licencia' => 'acceso_abierto',
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('Acceso Abierto')
            ->assertSee('🇪🇸')
            ->assertSee('Open Library')
            ->assertSee('Internet Archive')
            ->assertSee('Project Gutenberg')
            ->assertSee('MangaPlus')
            ->assertSee('Cervantes Virtual')
            ->assertSee('🎌')
            ->assertSee('🖋️');
    }

    public function test_reader_views_show_categories_and_active_loans_without_subscription_or_order_fields(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $reader = Reader::firstOrFail();

        $this->actingAs($admin)
            ->get(route('readers.create'))
            ->assertOk()
            ->assertSee('DNI / Código de Carnet *')
            ->assertSee('name="dni_codigo" value="" required', false)
            ->assertDontSee('plan_suscripcion', false)
            ->assertDontSee('plan_vence_at', false);

        $this->get(route('readers.edit', $reader))
            ->assertOk()
            ->assertSee('DNI / Código de Carnet *')
            ->assertSee('name="dni_codigo" value="'.$reader->dni_codigo.'" required', false)
            ->assertDontSee('plan_suscripcion', false)
            ->assertDontSee('plan_vence_at', false);

        $this->get(route('readers.index'))
            ->assertOk()
            ->assertSee('Préstamos activos')
            ->assertDontSee('orders_count', false)
            ->assertDontSee('pedidos');
    }
}
