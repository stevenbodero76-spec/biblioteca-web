<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\User;
use App\Models\Waitlist;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get("/books/{$book->id}")->assertStatus(200)->assertSee($book->titulo);
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
            ->get('/loans?buscar=' . urlencode($otroLoan->reader->dni_codigo))
            ->assertStatus(200)
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

    public function test_client_one_click_duration_and_server_calculated_yape_amount(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        $response = $this->actingAs($cliente)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 14,
            'metodo_pago' => 'yape_plin',
            'monto' => 0.01, // Intento de manipulación de precio en cliente
        ]);

        $loan = Loan::latest('id')->first();
        $response->assertRedirect(route('loans.ticket', $loan));

        $this->assertSame('yape_plin', $loan->metodo_pago);
        $this->assertSame((float) $book->precio_alquiler, (float) $loan->monto);
        $this->assertSame(now()->addDays(14)->toDateString(), $loan->fecha_devolucion_esperada->toDateString());
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

    public function test_plans_page_and_full_navbar_render_and_only_two_roles_exist(): void
    {
        $this->get('/planes')->assertStatus(200)->assertSee('Club NovaEther Pro');
        $this->get('/')->assertStatus(200)
            ->assertSee('Categorías')
            ->assertSee('Edición Digital')
            ->assertSee('Planes &amp; Cómo Funciona', false);

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

        $ajaxCatalog = $this->getJson('/books?partial=1&orden=menor_precio&buscar=One');
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
            'password' => \Illuminate\Support\Facades\Hash::make('secret123'),
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

    public function test_login_hides_demo_credentials_and_dashboard_shows_real_restock_and_financials(): void
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
            ->assertSee('Ingresos Verificados')
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
            'plan_suscripcion' => 'estudiante',
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
            'plan_suscripcion' => 'pro',
            'estado' => 'activo',
        ])->assertRedirect(route('readers.index'));

        $reader->refresh();
        $this->assertSame('valeria.sofia@example.com', $reader->user->fresh()->email);
        $this->assertSame('Valeria Sofía Campos Neyra', $reader->user->fresh()->name);
    }

    public function test_unpaid_loan_stays_pending_and_expires_automatically_releasing_stock(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $book = Book::where('isbn', '978-0451524935')->firstOrFail();
        $stockAntes = $book->stock_disponible;

        $this->actingAs($cliente)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 7,
            'metodo_pago' => 'yape_plin',
            'codigo_operacion' => 'YP-112233',
        ])->assertRedirect();

        $loan = Loan::latest('id')->firstOrFail();
        $this->assertSame('pendiente', $loan->estado_pago);
        $this->assertSame('YP-112233', $loan->codigo_operacion);
        $this->assertSame($stockAntes - 1, $book->fresh()->stock_disponible);

        // Expirar manualmente plazo de 24h y ejecutar comando de sincronización
        $loan->update(['expira_pago_at' => now()->subHour()]);
        $this->artisan('loans:sync-overdue')->assertExitCode(0);

        $this->assertSame('anulado', $loan->fresh()->estado_pago);
        $this->assertSame('devuelto', $loan->fresh()->estado);
        $this->assertSame($stockAntes, $book->fresh()->stock_disponible);

        // Confirmación manual por Admin en nuevo préstamo pendiente
        $this->actingAs($cliente)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 7,
            'metodo_pago' => 'mostrador',
        ]);
        $loan2 = Loan::latest('id')->firstOrFail();

        $this->actingAs($admin)
            ->patch("/loans/{$loan2->id}/confirmar-pago", ['codigo_operacion' => 'CAJA-001'])
            ->assertRedirect();

        $this->assertSame('pagado', $loan2->fresh()->estado_pago);
        $this->assertSame('CAJA-001', $loan2->fresh()->codigo_operacion);
    }

    public function test_free_plan_user_cannot_force_membership_zero_soles_rental_until_subscribed(): void
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
        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        $this->actingAs($mateo)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 7,
            'metodo_pago' => 'membresia',
        ])->assertSessionHasErrors('metodo_pago');

        // Al suscribirse a Club NovaEther Pro en /planes puede usar membresía
        $this->actingAs($mateo)->post('/planes/suscribir', [
            'plan_suscripcion' => 'pro',
        ])->assertRedirect();

        $this->assertSame('pro', $mateo->reader->fresh()->plan_suscripcion);
        $this->actingAs($mateo)->post('/loans', [
            'book_id' => $book->id,
            'duracion_dias' => 7,
            'metodo_pago' => 'membresia',
        ])->assertRedirect();
    }

    public function test_unified_cart_direct_sale_rental_and_karma_redemption_creates_order(): void
    {
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $bookVenta = Book::where('isbn', '978-0451524935')->firstOrFail(); // stock 5/5
        $bookAlq = Book::where('isbn', '978-0735211292')->firstOrFail(); // Hábitos Atómicos
        $stockTotalAntes = $bookVenta->stock_total;
        $stockDispAntes = $bookVenta->stock_disponible;
        $karmaAntes = $cliente->reader->puntos_karma; // 125 pts

        $this->actingAs($cliente)->postJson("/carrito/agregar/{$bookVenta->id}", [
            'tipo_operacion' => 'venta',
            'cantidad' => 2,
        ])->assertStatus(200)->assertJson(['cart_count' => 2]);

        $this->actingAs($cliente)->postJson("/carrito/agregar/{$bookAlq->id}", [
            'tipo_operacion' => 'alquiler',
            'duracion_dias' => 14,
        ])->assertStatus(200)->assertJson(['cart_count' => 3]);

        $response = $this->actingAs($cliente)->post('/carrito/checkout', [
            'metodo_pago' => 'tarjeta',
            'card_number' => '4557880012345678',
            'card_expiry' => '12/29',
            'card_cvv' => '321',
            'tipo_entrega' => 'delivery',
            'direccion_entrega' => 'Av. Javier Prado Este 2040, San Borja',
            'usar_karma' => '1',
        ]);

        $order = \App\Models\Order::latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame('pagado', $order->estado_pago);
        $this->assertSame('confirmado', $order->estado_pedido);
        $this->assertGreaterThan(0, $order->puntos_karma_usados);
        $this->assertSame($karmaAntes - $order->puntos_karma_usados, $cliente->reader->fresh()->puntos_karma);

        // La compra directa descuenta stock_total y stock_disponible permanentemente
        $this->assertSame($stockTotalAntes - 2, $bookVenta->fresh()->stock_total);
        $this->assertSame($stockDispAntes - 2, $bookVenta->fresh()->stock_disponible);

        // El alquiler dentro de la orden genera un Loan con Ticket QR
        $loanItem = $order->items()->where('tipo_operacion', 'alquiler')->firstOrFail();
        $this->assertNotNull($loanItem->loan_id);

        $this->actingAs($cliente)->get(route('orders.show', $order))->assertStatus(200)->assertSee($order->codigo_orden);
    }

    public function test_admin_post_sale_consoles_complaints_waitlists_and_review_moderation(): void
    {
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $complaint = \App\Models\Complaint::firstOrFail();

        $this->actingAs($admin)->get('/admin/reclamaciones')->assertStatus(200)->assertSee($complaint->codigo_reclamo);
        $this->actingAs($admin)->patch("/admin/reclamaciones/{$complaint->id}", [
            'estado' => 'resuelto',
            'respuesta_admin' => 'Se aplicó nota de crédito por el descuento indicado.',
        ])->assertRedirect();
        $this->assertSame('resuelto', $complaint->fresh()->estado);

        $this->actingAs($admin)->get('/admin/listas-espera')->assertStatus(200)->assertSee('La ciudad y los perros');

        $review = \App\Models\Review::firstOrFail();
        $this->actingAs($admin)->get('/admin/resenas')->assertStatus(200);
        $this->actingAs($admin)->patch("/reviews/{$review->id}/visibilidad")->assertRedirect();
        $this->assertFalse($review->fresh()->visible);

        // Ni otro cliente ni un visitante público ven reseñas ocultas por moderación
        \Illuminate\Support\Facades\Auth::logout();
        $this->get("/books/{$review->book_id}")->assertStatus(200)->assertDontSee($review->comentario);
    }

    public function test_mobile_api_v1_authentication_catalog_and_orders(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(200)->assertJsonStructure(['data']);

        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'cliente@novaether.com',
            'password' => '12345678',
            'device_name' => 'android-pixel',
        ]);

        $loginRes->assertStatus(200)->assertJsonStructure(['token', 'token_type', 'user']);
        $token = $loginRes->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('user.email', 'cliente@novaether.com');

        $book = Book::where('stock_disponible', '>', 0)->firstOrFail();

        // La API rechaza pago con tarjeta si faltan datos o está expirada
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['book_id' => $book->id, 'tipo_operacion' => 'venta', 'cantidad' => 1],
                ],
                'metodo_pago' => 'tarjeta',
                'tipo_entrega' => 'tienda',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card_number', 'card_expiry', 'card_cvv']);

        $orderRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/orders', [
                'items' => [
                    ['book_id' => $book->id, 'tipo_operacion' => 'venta', 'cantidad' => 1],
                ],
                'metodo_pago' => 'tarjeta',
                'card_number' => '4557880012345678',
                'card_expiry' => '12/29',
                'card_cvv' => '321',
                'tipo_entrega' => 'tienda',
            ]);

        $orderRes->assertStatus(201)
            ->assertJsonPath('data.estado_pago', 'pagado');

        $orderId = $orderRes->json('data.id');
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/orders/{$orderId}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $orderId);

        // Nuevos endpoints móviles de perfil y reseñas
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/books/{$book->id}/reviews", [
                'rating' => 5,
                'comentario' => 'Excelente entrega y calidad desde la app móvil.',
                'es_spoiler' => false,
            ])
            ->assertStatus(201);
    }

    public function test_rol2_hardening_prevents_karma_farming_unpaid_delivery_and_cart_oversell(): void
    {
        // 1. PlanController no duplica +20 karma al alternar planes dentro de 30 días
        $cliente = User::where('email', 'cliente@novaether.com')->firstOrFail();
        $karmaBase = $cliente->reader->puntos_karma;

        $this->actingAs($cliente)->post('/planes/suscribir', ['plan_suscripcion' => 'free'])->assertRedirect();
        $this->actingAs($cliente)->post('/planes/suscribir', ['plan_suscripcion' => 'estudiante'])->assertRedirect();
        $this->actingAs($cliente)->post('/planes/suscribir', ['plan_suscripcion' => 'free'])->assertRedirect();
        $this->actingAs($cliente)->post('/planes/suscribir', ['plan_suscripcion' => 'pro'])->assertRedirect();

        $this->assertSame($karmaBase + 20, $cliente->reader->fresh()->puntos_karma);

        // 2. ReviewController no otorga +10 karma de nuevo si el usuario borra y republica reseña en la misma obra
        $bookSinResena = Book::where('isbn', '978-0451524935')->firstOrFail();
        $this->actingAs($cliente)->post("/books/{$bookSinResena->id}/reviews", [
            'rating' => 5,
            'comentario' => 'Primera opinión verificada en plataforma.',
        ])->assertRedirect();
        $karmaTrasPrimera = $cliente->reader->fresh()->puntos_karma;
        $this->assertSame($karmaBase + 30, $karmaTrasPrimera);

        $reviewCreada = \App\Models\Review::where('book_id', $bookSinResena->id)
            ->where('user_id', $cliente->id)
            ->firstOrFail();
        $this->actingAs($cliente)->delete("/reviews/{$reviewCreada->id}")->assertRedirect();

        $this->actingAs($cliente)->post("/books/{$bookSinResena->id}/reviews", [
            'rating' => 5,
            'comentario' => 'Segunda reseña tras borrar la primera.',
        ])->assertRedirect();
        $this->assertSame($karmaTrasPrimera, $cliente->reader->fresh()->puntos_karma);

        // 3. CartService bloquea combinar venta + alquiler cuando supera stock_disponible
        $bookUno = Book::where('stock_disponible', '>', 0)->firstOrFail();
        $bookUno->update(['stock_total' => 1, 'stock_disponible' => 1]);

        $this->actingAs($cliente)->postJson("/carrito/agregar/{$bookUno->id}", [
            'tipo_operacion' => 'venta',
            'cantidad' => 1,
        ])->assertStatus(200);

        $this->actingAs($cliente)->postJson("/carrito/agregar/{$bookUno->id}", [
            'tipo_operacion' => 'alquiler',
            'duracion_dias' => 7,
        ])->assertStatus(422)->assertJsonValidationErrors('cantidad');

        // 4. OrderController bloquea marcar 'entregado' si el pago aún está pendiente
        $admin = User::where('email', 'admin@novaether.com')->firstOrFail();
        $orderPendiente = \App\Models\Order::create([
            'codigo_orden' => 'ORD-TEST-PEND1',
            'user_id' => $cliente->id,
            'reader_id' => $cliente->reader->id,
            'metodo_pago' => 'yape_plin',
            'estado_pago' => 'pendiente',
            'estado_pedido' => 'pendiente',
            'subtotal' => 40.00,
            'descuento_karma' => 0.00,
            'puntos_karma_usados' => 0,
            'total' => 40.00,
            'tipo_entrega' => 'tienda',
        ]);

        $this->actingAs($admin)->patch("/orders/{$orderPendiente->id}/estado", [
            'estado_pedido' => 'entregado',
        ])->assertSessionHas('error');
        $this->assertSame('pendiente', $orderPendiente->fresh()->estado_pedido);

        // 5. BookController limpia archivos locales reemplazados y eliminados
        \Illuminate\Support\Facades\Storage::fake('public');
        $png1x1 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file1 = \Illuminate\Http\UploadedFile::fake()->createWithContent('cover1.png', $png1x1);
        $file2 = \Illuminate\Http\UploadedFile::fake()->createWithContent('cover2.png', $png1x1);

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
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($ruta1);

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
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($ruta1);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($ruta2);

        $this->actingAs($admin)->delete("/books/{$libroStorage->id}")->assertRedirect(route('books.index'));
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($ruta2);
    }
}
