<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\Review;
use App\Models\User;
use App\Models\Waitlist;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditRemediationAndWorkflowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_and_updates_for_client_and_admin_without_orders_model(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();

        $this->actingAs($cliente)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Mi Perfil y Puntos Nova')
            ->assertDontSee('Carrito');

        $this->actingAs($cliente)
            ->put(route('profile.update'), [
                'nombres' => 'Carlos Actualizado',
                'apellidos' => 'Mendoza Ríos',
                'telefono' => '999888777',
                'email' => 'cliente@novaether.com',
            ])
            ->assertRedirect();

        $this->assertSame('Carlos Actualizado', $cliente->reader->fresh()->nombres);

        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Administrador General');
    }

    public function test_external_import_creates_default_category_when_empty_and_rejects_unsafe_urls(): void
    {
        $admin = User::factory()->create(['rol' => 'admin']);

        $this->actingAs($admin)
            ->post(route('books.importar-externo'), [
                'titulo' => 'Don Quijote',
                'autor' => 'Miguel de Cervantes',
                'archivo_pdf' => 'https://archive.org/details/donquijote00cerv',
            ])
            ->assertRedirect();

        $libro = Book::where('titulo', 'Don Quijote')->firstOrFail();
        $this->assertSame('https://archive.org/embed/donquijote00cerv', $libro->archivo_pdf);
        $this->assertDatabaseHas('categories', ['nombre' => 'Clásicos y Dominio Público']);

        $this->actingAs($admin)
            ->post(route('books.importar-externo'), [
                'titulo' => 'Enlace Inseguro',
                'autor' => 'Tester',
                'archivo_pdf' => 'ftp://malicious.example.com/file.pdf',
            ])
            ->assertSessionHasErrors('archivo_pdf');
    }

    public function test_book_update_restocking_notifies_waitlist_and_cleans_replaced_local_files(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();

        $waitlist = Waitlist::where('estado', 'en_espera')->firstOrFail();
        $book = $waitlist->book;
        $this->assertSame(0, (int) $book->stock_disponible);

        Storage::fake('public');
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('temp-cover.png', $png1x1);

        $this->actingAs($admin)
            ->put(route('books.update', $book), [
                'category_id' => $book->category_id,
                'isbn' => $book->isbn,
                'titulo' => $book->titulo,
                'autor' => $book->autor,
                'formato' => $book->formato,
                'idioma' => '',
                'licencia' => '',
                'stock_total' => $book->stock_total + 1,
                'stock_disponible' => 1,
                'portada_file' => $file,
            ])
            ->assertRedirect(route('books.index'));

        $book->refresh();
        $rutaLocal = ltrim(substr($book->portada_url, strlen('/storage/')), '/');
        Storage::disk('public')->assertExists($rutaLocal);
        $this->assertSame('es', $book->idioma);
        $this->assertSame('acceso_abierto', $book->licencia);
        $this->assertSame('notificado', $waitlist->fresh()->estado);

        // Reemplazar archivo local por URL externa debe borrar el archivo físico antiguo
        $this->actingAs($admin)
            ->put(route('books.update', $book), [
                'category_id' => $book->category_id,
                'isbn' => $book->isbn,
                'titulo' => $book->titulo,
                'autor' => $book->autor,
                'formato' => $book->formato,
                'idioma' => 'es',
                'licencia' => 'acceso_abierto',
                'stock_total' => $book->stock_total,
                'stock_disponible' => $book->stock_disponible,
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9788420412146-L.jpg',
            ])
            ->assertRedirect(route('books.index'));

        Storage::disk('public')->assertMissing($rutaLocal);
    }

    public function test_favorites_exclude_hidden_reviews_and_show_reading_sources(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $book = Book::where('isbn', 'CC-TECH-2019')->firstOrFail();

        Review::where('book_id', $book->id)->where('user_id', '!=', $cliente->id)->update(['visible' => false]);

        $this->actingAs($cliente)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($book->titulo)
            ->assertSee('Open Library')
            ->assertSee('(1 reseña)');
    }

    public function test_client_can_renew_active_loan_and_self_return_digital_loan_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $digitalBook = Book::where('formato', 'digital')->where('stock_disponible', '>', 0)->firstOrFail();
        $physicalHybridBook = Book::where('formato', 'ambos')->where('stock_disponible', '>', 0)->firstOrFail();

        // Cliente pide préstamo digital y préstamo físico/híbrido
        $this->actingAs($cliente)->post(route('loans.store'), [
            'book_id' => $digitalBook->id,
            'duracion_dias' => 7,
        ])->assertRedirect();

        $loanDigital = Loan::where('book_id', $digitalBook->id)->where('reader_id', $cliente->reader->id)->latest('id')->firstOrFail();
        $vencimientoOriginal = $loanDigital->fecha_devolucion_esperada->copy();

        // Puede renovar +7 días si no hay cola de espera
        $this->actingAs($cliente)
            ->patch(route('loans.renovar', $loanDigital))
            ->assertRedirect(route('loans.index'));

        $this->assertTrue($loanDigital->fresh()->fecha_devolucion_esperada->equalTo($vencimientoOriginal->addDays(7)));

        // Si alguien entra a la cola de espera del mismo libro, ya no puede volver a renovarse
        $otroReader = Reader::where('id', '!=', $cliente->reader->id)->where('estado', 'activo')->firstOrFail();
        Waitlist::create(['book_id' => $digitalBook->id, 'reader_id' => $otroReader->id, 'estado' => 'en_espera']);
        $this->actingAs($cliente)
            ->patch(route('loans.renovar', $loanDigital))
            ->assertSessionHasErrors('loan');

        // Cliente puede autodevolver su préstamo digital (sin farmear karma en el mismo día)
        $karmaAntes = $cliente->reader->fresh()->puntos_karma;
        $this->actingAs($cliente)
            ->patch(route('loans.devolver-digital', $loanDigital))
            ->assertRedirect(route('loans.index'));

        $this->assertSame('devuelto', $loanDigital->fresh()->estado);
        $this->assertSame($karmaAntes, $cliente->reader->fresh()->puntos_karma);

        // No puede autodevolver un préstamo de formato físico/ambos
        $loanFisico = Loan::create([
            'book_id' => $physicalHybridBook->id,
            'reader_id' => $cliente->reader->id,
            'user_id' => $cliente->id,
            'fecha_prestamo' => now()->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays(7)->toDateString(),
            'estado' => 'prestado',
        ]);

        $this->actingAs($cliente)
            ->patch(route('loans.devolver-digital', $loanFisico))
            ->assertForbidden();
    }

    public function test_admin_can_crud_categories_with_book_protection(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();

        $this->actingAs($cliente)->get(route('admin.categories.index'))->assertRedirect(route('home'));

        $this->actingAs($admin)->get(route('admin.categories.index'))->assertOk();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'nombre' => 'Ensayo Filosófico Nuevo',
            'icono' => '🦉',
            'descripcion' => 'Colección de ensayos contemporáneos.',
        ])->assertRedirect();

        $cat = Category::where('nombre', 'Ensayo Filosófico Nuevo')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.categories.update', $cat), [
            'nombre' => 'Ensayo y Pensamiento Crítico',
            'icono' => '🦉',
            'descripcion' => 'Actualizada.',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'nombre' => 'Ensayo y Pensamiento Crítico']);

        // Categoría vacía se elimina sin problema
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $cat))->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $cat->id]);

        // Categoría con libros no se puede eliminar
        $conLibros = Category::has('books')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.categories.destroy', $conLibros))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $conLibros->id]);
    }

    public function test_mobile_api_v1_waitlists_review_delete_and_loan_renewal_work(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $token = $cliente->createApiToken('qa-suite');
        $digitalBook = Book::where('formato', 'digital')->where('stock_disponible', '>', 0)->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/waitlists')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $loanRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans', [
                'book_id' => $digitalBook->id,
                'duracion_dias' => 7,
            ])
            ->assertCreated();

        $loanId = $loanRes->json('data.id');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/loans/{$loanId}/renovar")
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson("/api/v1/loans/{$loanId}/devolver")
            ->assertOk();

        $review = Review::where('user_id', $cliente->id)->firstOrFail();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson("/api/v1/reviews/{$review->id}")
            ->assertOk();
    }

    public function test_guests_must_login_to_read_full_book_and_return_to_reading_room_after_login(): void
    {
        $this->seed(DatabaseSeeder::class);
        $embedBook = Book::where('isbn', 'PD-SCIFI-1949')->firstOrFail();
        $externalManga = Book::where('isbn', '978-1569319017')->firstOrFail();

        // Invitado en ficha informativa no ve iframe ni enlaces directos de lectura
        $this->get(route('books.show', $embedBook))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('Inicia sesión para leer');

        // Al intentar entrar a Sala de Lectura o Descarga sin sesión, redirige al login guardando URL de retorno
        $this->get(route('books.download', $embedBook))->assertRedirect(route('login'));
        $this->get(route('books.read', $embedBook))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'cliente@novaether.com',
            'password' => '12345678',
        ])->assertRedirect(route('books.read', $embedBook));

        // Usuario autenticado abre Sala de Lectura embebida y externa (MANGA Plus)
        $this->get(route('books.read', $embedBook))
            ->assertOk()
            ->assertSee('Sala de Lectura Exclusiva NOVAETHER™')
            ->assertSee('<iframe', false);

        $this->get(route('books.read', $externalManga))
            ->assertOk()
            ->assertSee('Abrir Obra Completa en Fuente Oficial')
            ->assertSee('https://mangaplus.shueisha.co.jp/titles/100020');
    }

    public function test_admin_can_manage_books_create_and_delete_from_admin_books_console(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();

        // Cliente no puede entrar al panel administrativo de libros
        $this->actingAs($cliente)
            ->get(route('admin.books.index'))
            ->assertRedirect(route('home'));

        // Admin accede desde dashboard/menú al listado administrativo de libros y ve botón Subir Libro
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.books.index'))
            ->assertSee(route('books.create'));

        $this->actingAs($admin)
            ->get(route('admin.books.index'))
            ->assertOk()
            ->assertSee('Gestión del Catálogo')
            ->assertSee('Subir / Publicar Libro');

        $category = Category::firstOrFail();
        $unloanedBook = Book::create([
            'category_id' => $category->id,
            'isbn' => '979-9999000011',
            'titulo' => 'Libro Temporal Admin Console',
            'autor' => 'Tester Admin',
            'formato' => 'digital',
            'archivo_pdf' => 'https://archive.org/embed/Frankenstein1818Edition',
            'stock_total' => 2,
            'stock_disponible' => 2,
        ]);

        $this->actingAs($admin)
            ->delete(route('books.destroy', $unloanedBook), ['redirect_to' => 'admin'])
            ->assertRedirect(route('admin.books.index'));

        $this->assertDatabaseMissing('books', ['id' => $unloanedBook->id]);
    }

    public function test_header_has_single_login_button_and_login_view_includes_inline_registration(): void
    {
        $this->seed(DatabaseSeeder::class);
        $book = Book::where('isbn', 'PD-SCIFI-1949')->firstOrFail();

        $this->get('/')
            ->assertOk()
            ->assertSee('Iniciar Sesión')
            ->assertDontSee('>Crear Cuenta<', false)
            ->assertDontSee('Crear Cuenta Gratis</a>', false);

        $this->get('/login')
            ->assertOk()
            ->assertSee('¿No tienes cuenta aún?')
            ->assertSee('id="btn-show-inline-register"', false)
            ->assertSee('action="'.route('register.post').'"', false)
            ->assertSee('name="nombres"', false);

        // Si un visitante intenta leer un libro y luego crea su cuenta desde el formulario de login, vuelve directo a la obra
        $this->get(route('books.read', $book))->assertRedirect(route('login'));

        $this->post(route('register.post'), [
            'nombres' => 'Lucia',
            'apellidos' => 'Fernandez',
            'email' => 'lucia.inline@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('books.read', $book));
    }

    public function test_registration_rejects_numbers_in_names_and_letters_in_numeric_fields(): void
    {
        $this->from('/login')->post(route('register.post'), [
            'auth_mode' => 'register',
            'nombres' => 'Carlos123',
            'apellidos' => 'Perez99',
            'dni_codigo' => 'ABC12345',
            'telefono' => '987ABCDEF',
            'email' => 'validacion.strict@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['nombres', 'apellidos', 'dni_codigo', 'telefono']);

        $this->assertDatabaseMissing('users', ['email' => 'validacion.strict@example.com']);
    }

    public function test_admin_navigation_hides_client_estanteria_and_renders_library_control_sidebar(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();

        // Admin en portada ni en dashboard ve enlace de Estantería personal de cliente
        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(route('favorites.index'))
            ->assertDontSee('>Estantería<', false)
            ->assertSee('Admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('favorites.index'))
            ->assertDontSee('>Estantería<', false)
            ->assertSee('id="admin-control-sidebar"', false)
            ->assertSee('ADMIN BIBLIOTECA')
            ->assertSee('BÓVEDA ABIERTA')
            ->assertSee('Resumen General')
            ->assertSee('EN VIVO')
            ->assertSee('Gestión de Libros')
            ->assertSee('Ver Catálogo Público');

        // Cliente normal sí ve su Estantería y NO ve el sidebar de Admin
        $this->actingAs($cliente)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('favorites.index'))
            ->assertSee('Estantería')
            ->assertDontSee('id="admin-control-sidebar"', false);
    }
}
