<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\Review;
use App\Models\User;
use App\Models\Waitlist;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BibliotecaSecurityAndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_catalog_and_book_detail_are_accessible_without_login(): void
    {
        $this->get('/')->assertStatus(200)->assertSee('NOVAETHER');
        $this->get('/books')->assertStatus(200);

        $book = Book::first();
        $this->get("/books/{$book->id}")
            ->assertStatus(200)
            ->assertSee($book->titulo)
            ->assertSee('Open Library')
            ->assertSee('Internet Archive')
            ->assertSee('Project Gutenberg')
            ->assertSee('loading="lazy"', false);
    }

    public function test_retired_cart_and_order_listing_redirect_to_catalog(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();

        $this->actingAs($cliente)
            ->get('/carrito')
            ->assertNotFound();

        $this->actingAs($cliente)
            ->get('/mis-pedidos')
            ->assertNotFound();

        $this->getJson('/api/v1/orders')->assertNotFound();
    }

    public function test_admin_book_forms_hide_commerce_prices(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $book = Book::firstOrFail();

        $this->actingAs($admin)
            ->get(route('books.create'))
            ->assertOk()
            ->assertDontSee('precio_alquiler')
            ->assertDontSee('precio_venta');

        $this->actingAs($admin)
            ->get(route('books.edit', $book))
            ->assertOk()
            ->assertDontSee('precio_alquiler')
            ->assertDontSee('precio_venta');
    }

    public function test_book_detail_connects_the_embedded_reader_and_related_works(): void
    {
        $category = Category::create([
            'nombre' => 'Lecturas conectadas de prueba',
            'icono' => '📚',
        ]);
        $otherCategory = Category::create([
            'nombre' => 'Otra temática conectada de prueba',
            'icono' => '📖',
        ]);
        $readingUrl = 'https://archive.org/embed/reader-test-book';
        $book = Book::create([
            'category_id' => $category->id,
            'isbn' => '979-0000000001',
            'titulo' => 'Obra principal de prueba',
            'autor' => 'Autora de prueba',
            'formato' => 'digital',
            'archivo_pdf' => $readingUrl,
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);

        $authorRelatedBook = Book::create([
            'category_id' => $otherCategory->id,
            'isbn' => '979-0000000002',
            'titulo' => 'Otra obra de la misma autora',
            'autor' => $book->autor,
            'formato' => 'digital',
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);
        $topicRelatedBook = Book::create([
            'category_id' => $category->id,
            'isbn' => '979-0000000003',
            'titulo' => 'Otra obra de la misma temática',
            'autor' => 'Otro autor de prueba',
            'formato' => 'digital',
            'stock_total' => 1,
            'stock_disponible' => 1,
        ]);

        $this->get("/books/{$book->id}")
            ->assertStatus(200)
            ->assertSee('Lector NOVAETHER™')
            ->assertSee($readingUrl)
            ->assertSee($authorRelatedBook->titulo)
            ->assertSee($topicRelatedBook->titulo);
    }

    public function test_external_search_returns_open_library_and_gutenberg_books_on_web_and_api_routes(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'openlibrary.org/search.json*' => Http::response([
                'docs' => [[
                    'key' => '/works/OL123W',
                    'title' => 'Obra de Open Library',
                    'author_name' => ['Autora Abierta'],
                    'first_publish_year' => 1925,
                    'ia' => ['open-library-reader'],
                ]],
            ]),
            'gutendex.com/books*' => Http::response([
                'results' => [[
                    'id' => 42,
                    'title' => 'Obra de Gutenberg',
                    'authors' => [['name' => 'Autor de Dominio Público']],
                    'formats' => [
                        'text/html' => 'https://www.gutenberg.org/files/42/42-h/42-h.htm',
                        'image/jpeg' => 'https://www.gutenberg.org/cache/epub/42/pg42.cover.medium.jpg',
                    ],
                ]],
            ]),
        ]);

        $this->getJson(route('books.external-search', ['q' => 'lectura mundial']))
            ->assertOk()
            ->assertJsonPath('meta.query', 'lectura mundial')
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('data.0.titulo', 'Obra de Open Library')
            ->assertJsonPath('data.0.archivo_pdf', 'https://archive.org/embed/open-library-reader')
            ->assertJsonPath('data.1.titulo', 'Obra de Gutenberg')
            ->assertJsonPath('data.1.archivo_pdf', 'https://www.gutenberg.org/files/42/42-h/42-h.htm');

        $this->getJson('/api/books/external-search?q=lectura%20mundial')
            ->assertOk()
            ->assertJsonPath('data.0.titulo', 'Obra de Open Library')
            ->assertJsonPath('data.1.titulo', 'Obra de Gutenberg');
    }

    public function test_register_creates_linked_user_and_reader_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'nombres' => 'Luciano',
            'apellidos' => 'Vargas Ruiz',
            'dni_codigo' => '79998888',
            'telefono' => '999111222',
            'email' => 'luciano.vargas@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = User::where('email', 'luciano.vargas@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('cliente', $user->rol);
        $this->assertNotNull($user->reader);
        $this->assertSame('79998888', $user->reader->dni_codigo);
    }

    public function test_public_registration_creates_reader_without_dni_code(): void
    {
        $this->post('/register', [
            'nombres' => 'Lucía',
            'apellidos' => 'Paredes',
            'email' => 'lucia.publica@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('readers', [
            'email' => 'lucia.publica@example.com',
            'dni_codigo' => null,
        ]);
    }

    public function test_loan_and_reader_forms_render_without_plan_or_payment_fields(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $reader = Reader::where('email', 'cliente@novaether.com')->firstOrFail();

        $this->actingAs($admin)->get('/loans/create')
            ->assertOk()
            ->assertDontSee('name="metodo_pago"', false)
            ->assertDontSee('name="codigo_operacion"', false)
            ->assertDontSee('name="pago_verificado_admin"', false);

        $this->actingAs($admin)->get('/readers')
            ->assertOk()
            ->assertDontSee('name="plan_suscripcion"', false);

        $this->actingAs($admin)->get('/readers/create')
            ->assertOk()
            ->assertDontSee('name="plan_suscripcion"', false);

        $this->actingAs($admin)->get("/readers/{$reader->id}/edit")
            ->assertOk()
            ->assertDontSee('name="plan_suscripcion"', false);
    }

    public function test_client_cannot_access_dashboard_or_readers_or_return_loans(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $loan = Loan::where('estado', 'prestado')->firstOrFail();

        $this->actingAs($cliente)
            ->get('/dashboard')
            ->assertRedirect(route('home'));

        $this->actingAs($cliente)
            ->get('/readers')
            ->assertRedirect(route('home'));

        $this->actingAs($cliente)
            ->patch("/loans/{$loan->id}/devolver")
            ->assertRedirect(route('home'));
    }

    public function test_client_cannot_create_loan_for_another_reader_idor_protection(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $otroReader = Reader::where('email', '!=', 'cliente@novaether.com')
            ->where('estado', 'activo')
            ->firstOrFail();
        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        $response = $this->actingAs($cliente)->post('/loans', [
            'book_id' => $book->id,
            'reader_id' => $otroReader->id, // Intento de suplantación IDOR
            'fecha_prestamo' => now()->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays(5)->toDateString(),
        ]);

        $loan = Loan::latest('id')->first();
        $response->assertRedirect(route('loans.ticket', $loan));

        // El backend debe haber forzado el reader_id del cliente autenticado
        $this->assertSame($cliente->reader->id, $loan->reader_id);
        $this->assertNotSame($otroReader->id, $loan->reader_id);
    }

    public function test_client_only_sees_own_loans_even_when_searching(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $waitlistBookIds = Waitlist::where('reader_id', $cliente->reader->id)->pluck('book_id');
        $otroLoan = Loan::where('reader_id', '!=', $cliente->reader->id)
            ->whereNotIn('book_id', $waitlistBookIds)
            ->firstOrFail();

        // Aunque busque por el DNI de otro lector o título ajeno, su préstamo no debe aparecer
        $this->actingAs($cliente)
            ->get('/loans?buscar='.urlencode($otroLoan->reader->dni_codigo))
            ->assertStatus(200)
            ->assertDontSee('S/ ')
            ->assertDontSee($otroLoan->reader->nombre_completo)
            ->assertDontSee($otroLoan->book->titulo);

        // No debe poder abrir el ticket QR de otro cliente
        $this->actingAs($cliente)
            ->get("/loans/{$otroLoan->id}/ticket")
            ->assertStatus(403);
    }

    public function test_loan_is_blocked_when_book_has_zero_stock(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $reader = Reader::where('estado', 'activo')->firstOrFail();
        $bookZero = Book::where('stock_disponible', 0)->firstOrFail();

        $this->actingAs($admin)
            ->post('/loans', [
                'book_id' => $bookZero->id,
                'reader_id' => $reader->id,
                'fecha_prestamo' => now()->toDateString(),
                'fecha_devolucion_esperada' => now()->addDays(5)->toDateString(),
            ])
            ->assertSessionHasErrors('book_id');
    }

    public function test_book_update_rejects_javascript_urls_and_inconsistent_stock(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $bookWithLoan = Book::whereHas('loans', fn ($q) => $q->whereIn('estado', ['prestado', 'vencido']))->firstOrFail();
        $activos = $bookWithLoan->prestamosActivosCount();

        // Rechaza URL con esquema javascript: (XSS)
        $this->actingAs($admin)
            ->put("/books/{$bookWithLoan->id}", [
                'category_id' => $bookWithLoan->category_id,
                'isbn' => $bookWithLoan->isbn,
                'titulo' => $bookWithLoan->titulo,
                'autor' => $bookWithLoan->autor,
                'formato' => $bookWithLoan->formato,
                'portada_url' => 'javascript:alert(1)',
                'stock_total' => $bookWithLoan->stock_total,
                'stock_disponible' => $bookWithLoan->stock_disponible,
            ])
            ->assertSessionHasErrors('portada_url');

        // Rechaza asignar stock_disponible ignorando préstamos activos
        $this->actingAs($admin)
            ->put("/books/{$bookWithLoan->id}", [
                'category_id' => $bookWithLoan->category_id,
                'isbn' => $bookWithLoan->isbn,
                'titulo' => $bookWithLoan->titulo,
                'autor' => $bookWithLoan->autor,
                'formato' => $bookWithLoan->formato,
                'stock_total' => $bookWithLoan->stock_total,
                'stock_disponible' => $bookWithLoan->stock_total, // Inválido porque hay $activos prestados
            ])
            ->assertSessionHasErrors('stock_disponible');

        $this->assertGreaterThan(0, $activos);
    }

    public function test_return_loan_restores_stock_and_notifies_waitlist(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $waitlist = Waitlist::where('estado', 'en_espera')->firstOrFail();
        $loan = Loan::where('book_id', $waitlist->book_id)
            ->whereIn('estado', ['prestado', 'vencido'])
            ->firstOrFail();

        $this->actingAs($admin)
            ->patch("/loans/{$loan->id}/devolver")
            ->assertRedirect(route('loans.index'));

        $this->assertSame('devuelto', $loan->fresh()->estado);
        $this->assertSame(1, $waitlist->book->fresh()->stock_disponible);
        $this->assertSame('notificado', $waitlist->fresh()->estado);
    }

    public function test_ticket_qr_page_renders_for_owner_and_admin(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $ownLoan = Loan::where('reader_id', $cliente->reader->id)->firstOrFail();

        $this->actingAs($cliente)
            ->get("/loans/{$ownLoan->id}/ticket")
            ->assertStatus(200)
            ->assertSee($ownLoan->codigoTicket())
            ->assertDontSee('S/ ')
            ->assertSee('data:image/svg+xml;base64,');
    }

    public function test_client_can_post_review_and_earn_karma_once_per_book(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $karmaInicial = $cliente->reader->puntos_karma;
        $book = Book::where('isbn', '978-0451524935')->firstOrFail(); // 1984 (sin reseña previa)

        $this->actingAs($cliente)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 5,
                'comentario' => 'Una obra distópica brillante y vigente hasta hoy.',
                'es_spoiler' => '1',
            ])
            ->assertRedirect();

        $this->assertSame($karmaInicial + 10, $cliente->reader->fresh()->puntos_karma);

        // Actualizar la misma reseña no duplica puntos Karma
        $this->actingAs($cliente)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 4,
                'comentario' => 'Releída y sigue siendo excelente.',
            ])
            ->assertRedirect();

        $this->assertSame($karmaInicial + 10, $cliente->reader->fresh()->puntos_karma);
        $this->get("/books/{$book->id}")->assertStatus(200)->assertSee('Releída y sigue siendo excelente.');
    }

    public function test_client_can_toggle_favorites_and_view_estanteria(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $book = Book::where('isbn', '978-0451524935')->firstOrFail();

        $this->actingAs($cliente)
            ->post("/books/{$book->id}/favorite")
            ->assertRedirect();

        $this->actingAs($cliente)
            ->get('/mi-estanteria')
            ->assertStatus(200)
            ->assertSee($book->titulo);

        // Toggle quita de favoritos
        $this->actingAs($cliente)
            ->post("/books/{$book->id}/favorite")
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $cliente->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($cliente)
            ->get('/mi-estanteria')
            ->assertStatus(200)
            ->assertDontSee("/books/{$book->id}");
    }

    public function test_on_time_return_awards_karma_points_to_reader(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $loan = Loan::where('estado', 'prestado')
            ->whereDate('fecha_devolucion_esperada', '>=', now()->toDateString())
            ->firstOrFail();
        $karmaAntes = $loan->reader->puntos_karma;

        $this->actingAs($admin)
            ->patch("/loans/{$loan->id}/devolver")
            ->assertRedirect(route('loans.index'));

        $this->assertSame($karmaAntes + 25, $loan->reader->fresh()->puntos_karma);
    }

    public function test_plan_routes_are_removed_and_public_catalog_has_no_plans_link(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();

        $this->get('/planes')->assertNotFound();
        $this->actingAs($cliente)->post('/planes/suscribir')->assertNotFound();
        $this->get('/')->assertStatus(200)
            ->assertSee('Categorías')
            ->assertDontSee('Planes &amp; Cómo Funciona', false);

        $this->assertSame(['admin', 'cliente'], User::orderBy('rol')->pluck('rol')->unique()->values()->all());
    }

    public function test_public_complaint_book_registers_valid_claim(): void
    {
        $this->get('/libro-de-reclamaciones')->assertStatus(200);

        $this->post('/libro-de-reclamaciones', [
            'nombre_completo' => 'María Elena Pérez',
            'dni_ce' => '74839201',
            'email' => 'maria.perez@example.com',
            'telefono' => '987111222',
            'tipo' => 'reclamo',
            'detalle' => 'El tomo llegó con una pequeña marca en el lomo exterior.',
            'pedido_consumidor' => 'Cambio por unidad sellada nueva.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'dni_ce' => '74839201',
            'tipo' => 'reclamo',
        ]);
    }

    public function test_live_ajax_catalog_filtering_sorting_and_favorite_json_endpoints(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Mejor Valorados')
            ->assertSee('js-quickview-btn')
            ->assertSee('nova-quickview-modal');

        $ajaxCatalog = $this->getJson('/books?partial=1&orden=mejor_valorados&buscar=One');
        $ajaxCatalog->assertStatus(200)
            ->assertJsonStructure(['html', 'total']);
        $this->assertGreaterThanOrEqual(1, $ajaxCatalog->json('total'));

        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $book = Book::where('isbn', '978-0451524935')->firstOrFail();

        $this->actingAs($cliente)
            ->postJson("/books/{$book->id}/favorite")
            ->assertStatus(200)
            ->assertJson([
                'favorito' => true,
                'book_id' => $book->id,
            ]);
    }

    public function test_suspended_client_is_blocked_from_login_and_existing_session(): void
    {
        $suspendidoReader = Reader::where('estado', 'suspendido')->firstOrFail();
        $user = User::create([
            'name' => $suspendidoReader->nombre_completo,
            'email' => $suspendidoReader->email,
            'rol' => 'cliente',
            'password' => Hash::make('secret123'),
        ]);
        $suspendidoReader->update(['user_id' => $user->id]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        // Si ya estaba autenticado y es suspendido, el middleware lo expulsa inmediatamente
        $this->actingAs($user)
            ->get('/loans')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_hides_demo_credentials_and_dashboard_shows_restock_without_financials(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertDontSee('Acceso rápido de demostración')
            ->assertDontSee('12345678')
            ->assertSee('¿Olvidaste tu contraseña?');

        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertDontSee('Probar bloqueo:')
            ->assertDontSee('Ingresos Verificados')
            ->assertDontSee('Pagos Pendientes de Verificación')
            ->assertDontSee('Cobrar')
            ->assertSee('Reponer «');
    }

    public function test_admin_creating_and_updating_reader_syncs_linked_user_account(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();

        $this->actingAs($admin)->post('/readers', [
            'dni_codigo' => '77112233',
            'nombres' => 'Valeria',
            'apellidos' => 'Campos Neyra',
            'email' => 'valeria.campos@example.com',
            'telefono' => '988776655',
            'tipo' => 'publico',
            'estado' => 'activo',
            'password' => 'claveSegura1',
        ])->assertRedirect(route('readers.index'));

        $reader = Reader::where('dni_codigo', '77112233')->firstOrFail();
        $this->assertNotNull($reader->user_id);
        $this->assertSame('valeria.campos@example.com', $reader->user->email);
        $this->assertSame('cliente', $reader->user->rol);

        $this->actingAs($admin)->put("/readers/{$reader->id}", [
            'dni_codigo' => '77112233',
            'nombres' => 'Valeria Sofía',
            'apellidos' => 'Campos Neyra',
            'email' => 'valeria.sofia@example.com',
            'telefono' => '988776655',
            'tipo' => 'docente',
            'estado' => 'activo',
        ])->assertRedirect(route('readers.index'));

        $reader->refresh();
        $this->assertSame('valeria.sofia@example.com', $reader->user->fresh()->email);
        $this->assertSame('Valeria Sofía Campos Neyra', $reader->user->fresh()->name);
    }

    public function test_active_public_reader_can_reserve_without_plan_or_payment_fields(): void
    {
        $this->post('/register', [
            'nombres' => 'Mateo',
            'apellidos' => 'Paredes Solís',
            'dni_codigo' => '76123456',
            'email' => 'mateo.free@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('home'));

        $mateo = User::where('email', 'mateo.free@example.com')->firstOrFail();
        $mateo->reader->update(['tipo' => 'publico']);
        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        $response = $this->actingAs($mateo)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 7,
        ]);

        $loan = Loan::latest('id')->firstOrFail();
        $response->assertRedirect(route('loans.ticket', $loan));
        $this->assertSame($mateo->reader->id, $loan->reader_id);
        $this->assertSame($book->id, $loan->book_id);
        $this->assertSame('prestado', $loan->estado);
    }

    public function test_public_book_download_endpoint_streams_local_pdf_or_redirects_to_open_source(): void
    {
        $book = Book::firstOrFail();

        $this->get(route('books.download', $book))
            ->assertRedirect();

        Storage::fake('public');
        Storage::disk('public')->put('pdfs/obra-libre.pdf', '%PDF-1.4 dummy');

        $category = Category::firstOrFail();
        $localPdfBook = Book::create([
            'category_id' => $category->id,
            'isbn' => '979-0000000999',
            'titulo' => 'Manual Abierto Local',
            'autor' => 'Comunidad NovaEther',
            'formato' => 'digital',
            'archivo_pdf' => '/storage/pdfs/obra-libre.pdf',
            'stock_total' => 2,
            'stock_disponible' => 2,
        ]);

        $this->get(route('books.download', $localPdfBook))
            ->assertOk()
            ->assertDownload('manual-abierto-local.pdf');
    }

    public function test_admin_post_sale_consoles_complaints_waitlists_and_review_moderation(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $complaint = Complaint::firstOrFail();

        $this->actingAs($admin)->get('/admin/reclamaciones')->assertStatus(200)->assertSee($complaint->codigo_reclamo);
        $this->actingAs($admin)->patch("/admin/reclamaciones/{$complaint->id}", [
            'estado' => 'resuelto',
            'respuesta_admin' => 'Se actualizó el enlace digital reportado.',
        ])->assertRedirect();
        $this->assertSame('resuelto', $complaint->fresh()->estado);

        $this->actingAs($admin)->get('/admin/listas-espera')->assertStatus(200)->assertSee('La ciudad y los perros');

        $review = Review::firstOrFail();
        $this->actingAs($admin)->get('/admin/resenas')->assertStatus(200);
        $this->actingAs($admin)->patch("/reviews/{$review->id}/visibilidad")->assertRedirect();
        $this->assertFalse($review->fresh()->visible);

        // Ni otro cliente ni un visitante público ven reseñas ocultas por moderación
        Auth::logout();
        $this->get("/books/{$review->book_id}")->assertStatus(200)->assertDontSee($review->comentario);
    }

    public function test_mobile_api_v1_authentication_catalog_and_free_loans(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(200)->assertJsonStructure(['data']);

        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'cliente@novaether.com',
            'password' => '12345678',
            'device_name' => 'android-pixel',
        ]);

        $loginRes->assertStatus(200)->assertJsonStructure(['token', 'token_type', 'user']);
        $token = $loginRes->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('user.email', 'cliente@novaether.com');

        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        $loanRes = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/loans', [
                'book_id' => $book->id,
                'duracion_dias' => 7,
            ]);

        $loanRes->assertStatus(201)
            ->assertJsonPath('data.book_id', $book->id)
            ->assertJsonPath('data.estado', 'prestado');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/loans')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        // Endpoints móviles de perfil y reseñas
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/books/{$book->id}/reviews", [
                'rating' => 5,
                'comentario' => 'Excelente lectura abierta desde la app móvil.',
                'es_spoiler' => false,
            ])
            ->assertStatus(201);
    }

    public function test_rol2_hardening_prevents_karma_farming_and_cleans_local_storage(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $karmaBase = $cliente->reader->puntos_karma;

        // ReviewController no otorga karma de nuevo si el usuario borra y republica una reseña.
        $bookSinResena = Book::where('isbn', '978-0451524935')->firstOrFail();
        $this->actingAs($cliente)->post("/books/{$bookSinResena->id}/reviews", [
            'rating' => 5,
            'comentario' => 'Primera opinión verificada en plataforma.',
        ])->assertRedirect();
        $karmaTrasPrimera = $cliente->reader->fresh()->puntos_karma;
        $this->assertSame($karmaBase + 10, $karmaTrasPrimera);

        $reviewCreada = Review::where('book_id', $bookSinResena->id)
            ->where('user_id', $cliente->id)
            ->firstOrFail();
        $this->actingAs($cliente)->delete("/reviews/{$reviewCreada->id}")->assertRedirect();

        $this->actingAs($cliente)->post("/books/{$bookSinResena->id}/reviews", [
            'rating' => 5,
            'comentario' => 'Segunda reseña tras borrar la primera.',
        ])->assertRedirect();
        $this->assertSame($karmaTrasPrimera, $cliente->reader->fresh()->puntos_karma);

        // BookController limpia archivos locales reemplazados y eliminados
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        Storage::fake('public');
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file1 = UploadedFile::fake()->createWithContent('cover1.png', $png1x1);
        $file2 = UploadedFile::fake()->createWithContent('cover2.png', $png1x1);

        $category = Category::firstOrFail();
        $this->actingAs($admin)->post('/books', [
            'category_id' => $category->id,
            'isbn' => '978-9999999901',
            'titulo' => 'Libro Prueba Limpieza Storage',
            'autor' => 'Tester QA',
            'formato' => 'ambos',
            'stock_total' => 2,
            'stock_disponible' => 2,
            'portada_file' => $file1,
        ])->assertRedirect(route('books.index'));

        $libroStorage = Book::where('isbn', '978-9999999901')->firstOrFail();
        $ruta1 = ltrim(substr($libroStorage->portada_url, strlen('/storage/')), '/');
        Storage::disk('public')->assertExists($ruta1);

        $this->actingAs($admin)->put("/books/{$libroStorage->id}", [
            'category_id' => $category->id,
            'isbn' => '978-9999999901',
            'titulo' => 'Libro Prueba Limpieza Storage',
            'autor' => 'Tester QA',
            'formato' => 'ambos',
            'stock_total' => 2,
            'stock_disponible' => 2,
            'portada_file' => $file2,
        ])->assertRedirect(route('books.index'));

        $ruta2 = ltrim(substr($libroStorage->fresh()->portada_url, strlen('/storage/')), '/');
        Storage::disk('public')->assertMissing($ruta1);
        Storage::disk('public')->assertExists($ruta2);

        $this->actingAs($admin)->delete("/books/{$libroStorage->id}")->assertRedirect(route('books.index'));
        Storage::disk('public')->assertMissing($ruta2);
    }
}
