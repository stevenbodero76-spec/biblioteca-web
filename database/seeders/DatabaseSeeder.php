<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Favorite;
use App\Models\KarmaMovement;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\Review;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cuentas principales (2 Roles: Administrador y Cliente)
        $admin = User::create([
            'name' => 'Administrador General · NovaEther',
            'email' => 'admin@novaether.com',
            'rol' => 'admin',
            'password' => Hash::make('12345678'),
        ]);

        $cliente = User::create([
            'name' => 'Carlos Alberto Mendoza Ríos',
            'email' => 'cliente@novaether.com',
            'rol' => 'cliente',
            'password' => Hash::make('12345678'),
        ]);

        // 2. Las 12 Categorías Comerciales (Librería Moderna + Bóveda Digital + Cómics/Manga)
        $categoriasData = [
            ['nombre' => 'Cómics, Manga y Novela Gráfica', 'icono' => '🎌', 'descripcion' => 'Manga shonen/seinen, cómics de superhéroes y novelas gráficas de colección.'],
            ['nombre' => 'Literatura y Ficción Bestseller', 'icono' => '📖', 'descripcion' => 'Grandes novelas contemporáneas, éxitos de ventas y clásicos universales.'],
            ['nombre' => 'Ciencia Ficción, Fantasía y Terror', 'icono' => '🚀', 'descripcion' => 'Mundos distópicos, sagas épicas, viajes intergalácticos y horror sobrenatural.'],
            ['nombre' => 'Tecnología, IA y Desarrollo (Tech)', 'icono' => '💻', 'descripcion' => 'Ingeniería de software, inteligencia artificial, arquitectura cloud y ciencia.'],
            ['nombre' => 'Misterio, Thriller y Policial', 'icono' => '🕵️', 'descripcion' => 'Novela negra, suspenso psicológico, crímenes y casos de investigación.'],
            ['nombre' => 'Negocios, Finanzas y Startups', 'icono' => '📈', 'descripcion' => 'Inversiones, mentalidad empresarial, liderazgo, cripto y marketing digital.'],
            ['nombre' => 'Desarrollo Personal y Mindset', 'icono' => '🌱', 'descripcion' => 'Productividad de alto rendimiento, hábitos, psicología práctica y bienestar.'],
            ['nombre' => 'Historia, Filosofía y Sociedad', 'icono' => '🏛️', 'descripcion' => 'Civilizaciones, pensamiento estratégico, geopolítica y crónicas de la humanidad.'],
            ['nombre' => 'Juvenil y Sagas de Aventura (YA)', 'icono' => '⚡', 'descripcion' => 'Fantasía juvenil, relatos ilustrados y sagas fenómeno a nivel mundial.'],
            ['nombre' => 'Arte, Cine, Diseño y Cultura Pop', 'icono' => '🎨', 'descripcion' => 'Concept art, dirección cinematográfica, fotografía, música y arquitectura.'],
            ['nombre' => 'Idiomas y Guías Internacionales', 'icono' => '🌍', 'descripcion' => 'Dominio de inglés, japonés, certificaciones globales y manuales prácticos.'],
            ['nombre' => 'Biografías de Íconos y Pioneros', 'icono' => '🖋️', 'descripcion' => 'La mente detrás de los grandes fundadores, artistas, científicos y leyendas.'],
        ];

        $categorias = [];
        foreach ($categoriasData as $cat) {
            $categorias[$cat['nombre']] = Category::create($cat);
        }

        // 3. Catálogo Comercial de Libros, Cómics y Mangas
        $librosData = [
            // --- CÓMICS, MANGA Y NOVELA GRÁFICA ---
            [
                'category_id' => $categorias['Cómics, Manga y Novela Gráfica']->id,
                'isbn' => '978-1569319017',
                'titulo' => 'One Piece Vol. 1: Romance Dawn',
                'autor' => 'Eiichiro Oda',
                'editorial' => 'Shueisha / Viz Media',
                'anio_publicacion' => 1997,
                'formato' => 'ambos',
                'sinopsis' => 'Monkey D. Luffy zarpa al Grand Line en busca del legendario tesoro One Piece. El manga más vendido de la historia en edición física y digital de alta definición.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781569319017-L.jpg',
                'archivo_pdf' => 'https://mangaplus.shueisha.co.jp/titles/200016',
                'ubicacion_estante' => 'Vault Manga M-01 · Streaming HD',
                'stock_total' => 6,
                'stock_disponible' => 5,
            ],
            [
                'category_id' => $categorias['Cómics, Manga y Novela Gráfica']->id,
                'isbn' => '978-1401207526',
                'titulo' => 'Batman: Año Uno (Deluxe Edition)',
                'autor' => 'Frank Miller & David Mazzucchelli',
                'editorial' => 'DC Comics Black Label',
                'anio_publicacion' => 1987,
                'formato' => 'ambos',
                'sinopsis' => 'La obra maestra noir que redefinió el origen de Bruce Wayne como el Caballero Oscuro y la alianza con James Gordon en las calles de Gotham.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781401207526-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781401207526',
                'ubicacion_estante' => 'Vault Cómics DC-01',
                'stock_total' => 4,
                'stock_disponible' => 4,
            ],
            [
                'category_id' => $categorias['Cómics, Manga y Novela Gráfica']->id,
                'isbn' => '978-1401245252',
                'titulo' => 'Watchmen (Absolute Collector)',
                'autor' => 'Alan Moore & Dave Gibbons',
                'editorial' => 'DC Comics / Vertigo',
                'anio_publicacion' => 1986,
                'formato' => 'digital',
                'sinopsis' => '¿Quién vigila a los vigilantes? En un mundo al borde del colapso nuclear, una conspiración contra antiguos superhéroes cambia la historia del cómic para siempre.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781401245252-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781401245252',
                'ubicacion_estante' => 'NovaEther Cloud Comic',
                'stock_total' => 5,
                'stock_disponible' => 5,
            ],
            [
                'category_id' => $categorias['Cómics, Manga y Novela Gráfica']->id,
                'isbn' => '978-1569319000',
                'titulo' => 'Naruto Vol. 1: Uzumaki Naruto',
                'autor' => 'Masashi Kishimoto',
                'editorial' => 'Shueisha / Panini Manga',
                'anio_publicacion' => 1999,
                'formato' => 'ambos',
                'sinopsis' => 'El inicio de la leyenda ninja de Naruto Uzumaki en la Aldea Oculta de la Hoja y su camino indomable para convertirse en Hokage.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781569319000-L.jpg',
                'archivo_pdf' => 'https://mangaplus.shueisha.co.jp/',
                'ubicacion_estante' => 'Vault Manga M-02',
                'stock_total' => 4,
                'stock_disponible' => 4,
            ],
            [
                'category_id' => $categorias['Cómics, Manga y Novela Gráfica']->id,
                'isbn' => '978-1612620244',
                'titulo' => 'Attack on Titan (Shingeki no Kyojin) Vol. 1',
                'autor' => 'Hajime Isayama',
                'editorial' => 'Kodansha Comics',
                'anio_publicacion' => 2009,
                'formato' => 'ambos',
                'sinopsis' => 'La humanidad sobrevive tras enormes murallas hasta que la aparición del Titán Colosal desata una batalla épica por la libertad.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781612620244-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781612620244',
                'ubicacion_estante' => 'Vault Manga M-03',
                'stock_total' => 1,
                'stock_disponible' => 0,
            ],

            // --- LITERATURA Y FICCIÓN BESTSELLER ---
            [
                'category_id' => $categorias['Literatura y Ficción Bestseller']->id,
                'isbn' => '978-8497592208',
                'titulo' => 'Cien años de soledad',
                'autor' => 'Gabriel García Márquez',
                'editorial' => ' Vintage Español / Debolsillo',
                'anio_publicacion' => 1967,
                'formato' => 'ambos',
                'sinopsis' => 'La saga inmortal de la familia Buendía en el mítico pueblo de Macondo. Una joya indispensable de la literatura mundial.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9788497592208-L.jpg',
                'archivo_pdf' => 'https://www.cervantesvirtual.com/',
                'ubicacion_estante' => 'Salón Dorado A-01',
                'stock_total' => 5,
                'stock_disponible' => 4,
            ],
            [
                'category_id' => $categorias['Literatura y Ficción Bestseller']->id,
                'isbn' => '978-8420412146',
                'titulo' => 'La ciudad y los perros (Edición Conmemorativa)',
                'autor' => 'Mario Vargas Llosa',
                'editorial' => 'Alfaguara',
                'anio_publicacion' => 1963,
                'formato' => 'ambos',
                'sinopsis' => 'La novela ganadora del Premio Biblioteca Breve que catapultó el boom latinoamericano con una narrativa feroz e inolvidable.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9788420412146-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9788420412146',
                'ubicacion_estante' => 'Salón Dorado A-02',
                'stock_total' => 1,
                'stock_disponible' => 0,
            ],

            // --- CIENCIA FICCIÓN, FANTASÍA Y TERROR ---
            [
                'category_id' => $categorias['Ciencia Ficción, Fantasía y Terror']->id,
                'isbn' => '978-0441172719',
                'titulo' => 'Dune (Crónicas de Arrakis)',
                'autor' => 'Frank Herbert',
                'editorial' => 'Ace Sci-Fi / Nova',
                'anio_publicacion' => 1965,
                'formato' => 'ambos',
                'sinopsis' => 'En el desértico planeta Arrakis, Paul Atreides lidera una rebelión interestelar por el recurso más valioso del universo: la especia melange.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780441172719-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780441172719',
                'ubicacion_estante' => 'Sector Sci-Fi B-01',
                'stock_total' => 4,
                'stock_disponible' => 3,
            ],
            [
                'category_id' => $categorias['Ciencia Ficción, Fantasía y Terror']->id,
                'isbn' => '978-0451524935',
                'titulo' => '1984 (Edición Definitiva)',
                'autor' => 'George Orwell',
                'editorial' => 'Signet Classics',
                'anio_publicacion' => 1949,
                'formato' => 'ambos',
                'sinopsis' => 'La distopía más influyente del siglo XX: vigilancia total, manipulación de la verdad y la rebelión de Winston Smith contra el Gran Hermano.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780451524935',
                'ubicacion_estante' => 'Sector Sci-Fi B-04',
                'stock_total' => 5,
                'stock_disponible' => 5,
            ],

            // --- TECNOLOGÍA, IA Y DESARROLLO (TECH) ---
            [
                'category_id' => $categorias['Tecnología, IA y Desarrollo (Tech)']->id,
                'isbn' => '978-0132350884',
                'titulo' => 'Clean Code: Arquitectura y Código Limpio',
                'autor' => 'Robert C. Martin',
                'editorial' => 'Prentice Hall Tech',
                'anio_publicacion' => 2008,
                'formato' => 'digital',
                'sinopsis' => 'El manual imprescindible para ingenieros de software y arquitectos digitales que buscan excelencia técnica en cada línea de código.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780132350884',
                'ubicacion_estante' => 'NovaEther Tech Cloud',
                'stock_total' => 6,
                'stock_disponible' => 5,
            ],
            [
                'category_id' => $categorias['Tecnología, IA y Desarrollo (Tech)']->id,
                'isbn' => '978-0201633610',
                'titulo' => 'Design Patterns: Patrones de Software Moderno',
                'autor' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
                'editorial' => 'Addison-Wesley Professional',
                'anio_publicacion' => 1994,
                'formato' => 'ambos',
                'sinopsis' => 'Los 23 patrones de diseño esenciales que sustentan los frameworks y sistemas empresariales más potentes del mundo.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780201633610',
                'ubicacion_estante' => 'Sector Tech T-02',
                'stock_total' => 4,
                'stock_disponible' => 4,
            ],

            // --- MISTERIO, THRILLER Y POLICIAL ---
            [
                'category_id' => $categorias['Misterio, Thriller y Policial']->id,
                'isbn' => '978-0345450661',
                'titulo' => 'El psicoanalista',
                'autor' => 'John Katzenbach',
                'editorial' => 'Ballantine Books / Ediciones B',
                'anio_publicacion' => 2002,
                'formato' => 'ambos',
                'sinopsis' => '«Feliz 53 cumpleaños, doctor. Bienvenido al primer día de su muerte». Un juego de ajedrez psicológico que no podrás soltar.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780345450661-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780345450661',
                'ubicacion_estante' => 'Sector Noir C-02',
                'stock_total' => 3,
                'stock_disponible' => 3,
            ],

            // --- NEGOCIOS, FINANZAS Y STARTUPS ---
            [
                'category_id' => $categorias['Negocios, Finanzas y Startups']->id,
                'isbn' => '978-1612680194',
                'titulo' => 'Padre Rico, Padre Pobre',
                'autor' => 'Robert T. Kiyosaki',
                'editorial' => 'Plata Publishing',
                'anio_publicacion' => 1997,
                'formato' => 'ambos',
                'sinopsis' => 'El bestseller global sobre libertad financiera, creación de activos, inversión inteligente y mentalidad emprendedora.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781612680194-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781612680194',
                'ubicacion_estante' => 'Business Hub E-01',
                'stock_total' => 5,
                'stock_disponible' => 5,
            ],

            // --- DESARROLLO PERSONAL Y MINDSET ---
            [
                'category_id' => $categorias['Desarrollo Personal y Mindset']->id,
                'isbn' => '978-0735211292',
                'titulo' => 'Hábitos Atómicos',
                'autor' => 'James Clear',
                'editorial' => 'Avery / Penguin Random House',
                'anio_publicacion' => 2018,
                'formato' => 'ambos',
                'sinopsis' => 'El método definitivo respaldado por la neurociencia para construir hábitos extraordinarios y alcanzar el máximo potencial.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780735211292',
                'ubicacion_estante' => 'Mindset Lounge F-03',
                'stock_total' => 4,
                'stock_disponible' => 4,
            ],

            // --- HISTORIA, FILOSOFÍA Y SOCIEDAD ---
            [
                'category_id' => $categorias['Historia, Filosofía y Sociedad']->id,
                'isbn' => '978-0062316097',
                'titulo' => 'Sapiens: De animales a dioses',
                'autor' => 'Yuval Noah Harari',
                'editorial' => 'Harper / Debate',
                'anio_publicacion' => 2014,
                'formato' => 'ambos',
                'sinopsis' => 'Cómo nuestra especie logró dominar el planeta, crear imperios, dinero, ciencia e inteligencia artificial.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780062316097',
                'ubicacion_estante' => 'Galería Historia D-01',
                'stock_total' => 4,
                'stock_disponible' => 4,
            ],

            // --- JUVENIL Y SAGAS DE AVENTURA ---
            [
                'category_id' => $categorias['Juvenil y Sagas de Aventura (YA)']->id,
                'isbn' => '978-0156012195',
                'titulo' => 'El Principito (Edición Ilustrada)',
                'autor' => 'Antoine de Saint-Exupéry',
                'editorial' => 'Harvest / Salamandra',
                'anio_publicacion' => 1943,
                'formato' => 'ambos',
                'sinopsis' => '«Lo esencial es invisible a los ojos». Una obra atemporal con sus acuarelas originales restauradas.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780156012195-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780156012195',
                'ubicacion_estante' => 'Zona Aventura G-01',
                'stock_total' => 6,
                'stock_disponible' => 6,
            ],

            // --- ARTE, CINE, DISEÑO Y CULTURA POP ---
            [
                'category_id' => $categorias['Arte, Cine, Diseño y Cultura Pop']->id,
                'isbn' => '978-0714832470',
                'titulo' => 'La Historia del Arte (Edición Coleccionista)',
                'autor' => 'E. H. Gombrich',
                'editorial' => 'Phaidon Press',
                'anio_publicacion' => 1950,
                'formato' => 'ambos',
                'sinopsis' => 'El libro de arte y estética visual más célebre del mundo en formato de lujo.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9780714832470-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9780714832470',
                'ubicacion_estante' => 'Galería Visual H-02',
                'stock_total' => 2,
                'stock_disponible' => 2,
            ],

            // --- IDIOMAS Y GUÍAS INTERNACIONALES ---
            [
                'category_id' => $categorias['Idiomas y Guías Internacionales']->id,
                'isbn' => '978-1108457651',
                'titulo' => 'English Grammar in Use (5th Global Edition)',
                'autor' => 'Raymond Murphy',
                'editorial' => 'Cambridge Press',
                'anio_publicacion' => 2019,
                'formato' => 'digital',
                'sinopsis' => 'El bestseller mundial para dominar el inglés profesional e internacional de forma autodidacta.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781108457651-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781108457651',
                'ubicacion_estante' => 'NovaEther Global Cloud',
                'stock_total' => 8,
                'stock_disponible' => 8,
            ],

            // --- BIOGRAFÍAS DE ÍCONOS Y PIONEROS ---
            [
                'category_id' => $categorias['Biografías de Íconos y Pioneros']->id,
                'isbn' => '978-1451648539',
                'titulo' => 'Steve Jobs: La Biografía Oficial',
                'autor' => 'Walter Isaacson',
                'editorial' => 'Simon & Schuster / Debate',
                'anio_publicacion' => 2011,
                'formato' => 'ambos',
                'sinopsis' => 'El retrato definitivo sobre innovación, diseño obsesivo y liderazgo tecnológico del fundador de Apple y Pixar.',
                'portada_url' => 'https://covers.openlibrary.org/b/isbn/9781451648539-L.jpg',
                'archivo_pdf' => 'https://openlibrary.org/isbn/9781451648539',
                'ubicacion_estante' => 'Salón Pioneros I-01',
                'stock_total' => 3,
                'stock_disponible' => 3,
            ],
        ];

        $librosPorIsbn = [];
        foreach ($librosData as $libro) {
            $libro['idioma'] = $libro['isbn'] === '978-1108457651' ? 'en' : 'es';
            $libro['licencia'] = ($libro['anio_publicacion'] ?? 2000) <= 1955 ? 'dominio_publico' : 'acceso_abierto';

            $creado = Book::create($libro);
            $librosPorIsbn[$libro['isbn']] = $creado;
        }

        // 4. Lectores de la Biblioteca Pública Abierta (Estudiante, Docente, Lector Público)
        $lectoresData = [
            ['user_id' => $cliente->id, 'dni_codigo' => '72849102', 'nombres' => 'Carlos Alberto', 'apellidos' => 'Mendoza Ríos', 'email' => 'cliente@novaether.com', 'telefono' => '987654321', 'tipo' => 'estudiante', 'estado' => 'activo', 'puntos_karma' => 125],
            ['dni_codigo' => '74102938', 'nombres' => 'Lucía Fernanda', 'apellidos' => 'Quispe Salazar', 'email' => 'lucia.quispe@outlook.com', 'telefono' => '912345678', 'tipo' => 'docente', 'estado' => 'activo', 'puntos_karma' => 210],
            ['dni_codigo' => '41928374', 'nombres' => 'Roberto', 'apellidos' => 'Paredes Vargas', 'email' => 'rparedes@novaether.club', 'telefono' => '998877665', 'tipo' => 'docente', 'estado' => 'activo', 'puntos_karma' => 90],
            ['dni_codigo' => '76543210', 'nombres' => 'Andrea Valeria', 'apellidos' => 'Rojas Castillo', 'email' => 'andrea.rojas@gmail.com', 'telefono' => '955443322', 'tipo' => 'publico', 'estado' => 'activo', 'puntos_karma' => 75],
            ['dni_codigo' => '70918273', 'nombres' => 'Diego Alejandro', 'apellidos' => 'Torres Huamán', 'email' => 'diego.torres@hotmail.com', 'telefono' => '944332211', 'tipo' => 'publico', 'estado' => 'suspendido', 'puntos_karma' => 10],
        ];

        $lectores = [];
        foreach ($lectoresData as $lec) {
            $lectores[] = Reader::create($lec);
        }

        // 5. Historial de Préstamos Públicos Gratuitos
        Loan::create([
            'book_id' => $librosPorIsbn['978-1569319017']->id, // One Piece Vol. 1
            'reader_id' => $lectores[0]->id,
            'user_id' => $admin->id,
            'fecha_prestamo' => now()->subDays(3)->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays(4)->toDateString(),
            'fecha_devolucion_real' => null,
            'estado' => 'prestado',
            'metodo_pago' => 'membresia',
            'monto' => 0.00,
            'estado_pago' => 'pagado',
            'fecha_pago' => now()->subDays(3),
            'observaciones' => 'Préstamo público gratuito + acceso a lectura digital.',
        ]);

        Loan::create([
            'book_id' => $librosPorIsbn['978-8420412146']->id, // La ciudad y los perros (stock 0)
            'reader_id' => $lectores[1]->id,
            'user_id' => $admin->id,
            'fecha_prestamo' => now()->subDays(10)->toDateString(),
            'fecha_devolucion_esperada' => now()->subDays(2)->toDateString(),
            'fecha_devolucion_real' => null,
            'estado' => 'vencido',
            'metodo_pago' => 'membresia',
            'monto' => 0.00,
            'estado_pago' => 'pagado',
            'fecha_pago' => now()->subDays(10),
            'observaciones' => 'Última unidad en circulación. Stock actual agotado (0 disponibles).',
        ]);

        Loan::create([
            'book_id' => $librosPorIsbn['978-1612620244']->id, // Attack on Titan Vol. 1 (stock 0)
            'reader_id' => $lectores[2]->id,
            'user_id' => $admin->id,
            'fecha_prestamo' => now()->subDays(5)->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays(2)->toDateString(),
            'fecha_devolucion_real' => null,
            'estado' => 'prestado',
            'metodo_pago' => 'membresia',
            'monto' => 0.00,
            'estado_pago' => 'pagado',
            'fecha_pago' => now()->subDays(5),
            'observaciones' => 'Préstamo público gratuito de tomo manga.',
        ]);

        Loan::create([
            'book_id' => $librosPorIsbn['978-0132350884']->id, // Clean Code
            'reader_id' => $lectores[3]->id,
            'user_id' => $admin->id,
            'fecha_prestamo' => now()->subDays(1)->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays(6)->toDateString(),
            'fecha_devolucion_real' => null,
            'estado' => 'prestado',
            'metodo_pago' => 'membresia',
            'monto' => 0.00,
            'estado_pago' => 'pagado',
            'fecha_pago' => now()->subDays(1),
            'observaciones' => 'Préstamo gratuito activado para lector.',
        ]);

        Loan::create([
            'book_id' => $librosPorIsbn['978-1612680194']->id, // Padre Rico, Padre Pobre (Devuelto)
            'reader_id' => $lectores[0]->id,
            'user_id' => $admin->id,
            'fecha_prestamo' => now()->subDays(12)->toDateString(),
            'fecha_devolucion_esperada' => now()->subDays(5)->toDateString(),
            'fecha_devolucion_real' => now()->subDays(6)->toDateString(),
            'estado' => 'devuelto',
            'metodo_pago' => 'membresia',
            'monto' => 0.00,
            'estado_pago' => 'pagado',
            'fecha_pago' => now()->subDays(12),
            'observaciones' => 'Devolución puntual completada (+25 Puntos Nova).',
        ]);

        // 6. Lista de espera de ejemplo para libro sin stock
        Waitlist::create([
            'book_id' => $librosPorIsbn['978-8420412146']->id, // La ciudad y los perros (stock 0)
            'reader_id' => $lectores[0]->id,
            'estado' => 'en_espera',
        ]);

        // 7. Reseñas reales variadas (con protección anti-spoilers)
        Review::create([
            'book_id' => $librosPorIsbn['978-1569319017']->id, // One Piece Vol. 1
            'user_id' => $cliente->id,
            'rating' => 5,
            'comentario' => 'Tomo impecable, llegó en bolsa coleccionista sin una sola esquina doblada. El inicio de la aventura de Luffy es inolvidable.',
            'es_spoiler' => false,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-1569319017']->id,
            'user_id' => $admin->id,
            'rating' => 5,
            'comentario' => 'La escena donde Shanks entrega el sombrero de paja a Luffy sigue siendo de los mejores momentos del manga shonen.',
            'es_spoiler' => true,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-1401207526']->id, // Batman Año Uno
            'user_id' => $cliente->id,
            'rating' => 5,
            'comentario' => 'Arte noir increíble y guion brutal de Frank Miller.',
            'es_spoiler' => false,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-1612620244']->id, // Attack on Titan Vol. 1
            'user_id' => $admin->id,
            'rating' => 5,
            'comentario' => 'El giro cuando aparece el Titán Colosal frente a la Muralla María te deja helado desde el capítulo 1.',
            'es_spoiler' => true,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-0132350884']->id, // Clean Code
            'user_id' => $admin->id,
            'rating' => 5,
            'comentario' => 'Lectura obligatoria si programas en Laravel o cualquier lenguaje moderno.',
            'es_spoiler' => false,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-0132350884']->id,
            'user_id' => $cliente->id,
            'rating' => 4,
            'comentario' => 'Excelente para mejorar la calidad del código, aunque algunos ejemplos en Java requieren práctica.',
            'es_spoiler' => false,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-0441172719']->id, // Dune
            'user_id' => $admin->id,
            'rating' => 4,
            'comentario' => 'Construcción de mundo espectacular, arranca lento pero atrapa por completo.',
            'es_spoiler' => false,
        ]);
        Review::create([
            'book_id' => $librosPorIsbn['978-0735211292']->id, // Hábitos Atómicos
            'user_id' => $cliente->id,
            'rating' => 5,
            'comentario' => 'Súper práctico para aplicar desde el día 1 en productividad y estudio.',
            'es_spoiler' => false,
        ]);

        // 8. Favoritos iniciales en Mi Estantería del cliente demo
        Favorite::create(['user_id' => $cliente->id, 'book_id' => $librosPorIsbn['978-1569319017']->id]);
        Favorite::create(['user_id' => $cliente->id, 'book_id' => $librosPorIsbn['978-0132350884']->id]);
        Favorite::create(['user_id' => $cliente->id, 'book_id' => $librosPorIsbn['978-0441172719']->id]);

        // 9. Historial de Karma e Hoja de Reclamación de ejemplo para consola Admin
        KarmaMovement::create([
            'reader_id' => $lectores[0]->id,
            'puntos' => 50,
            'motivo' => 'Bono inicial de bienvenida NOVAETHER™',
        ]);
        KarmaMovement::create([
            'reader_id' => $lectores[0]->id,
            'puntos' => 25,
            'motivo' => 'Devolución puntual de «Padre Rico, Padre Pobre»',
        ]);

        Complaint::create([
            'codigo_reclamo' => 'LRC-' . date('Y') . '-DEMO01',
            'nombre_completo' => 'Andrea Valeria Rojas Castillo',
            'dni_ce' => '76543210',
            'email' => 'andrea.rojas@gmail.com',
            'telefono' => '955443322',
            'tipo' => 'reclamo',
            'detalle' => 'El código de descuento de campaña no se aplicó al intentar reservar por segunda vez.',
            'pedido_consumidor' => 'Revisión del cálculo de descuento en tienda.',
            'estado' => 'registrado',
        ]);
    }
}
