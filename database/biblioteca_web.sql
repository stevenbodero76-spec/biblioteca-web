/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: biblioteca_web
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `biblioteca_web`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `biblioteca_web` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `biblioteca_web`;

--
-- Table structure for table `books`
--

DROP TABLE IF EXISTS `books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `books` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `isbn` varchar(30) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `autor` varchar(150) NOT NULL,
  `editorial` varchar(100) DEFAULT NULL,
  `anio_publicacion` smallint(5) unsigned DEFAULT NULL,
  `formato` enum('fisico','digital','ambos') NOT NULL DEFAULT 'ambos',
  `sinopsis` text DEFAULT NULL,
  `portada_url` varchar(255) DEFAULT NULL,
  `archivo_pdf` varchar(255) DEFAULT NULL,
  `ubicacion_estante` varchar(50) DEFAULT NULL,
  `stock_total` int(10) unsigned NOT NULL DEFAULT 1,
  `stock_disponible` int(10) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `books_isbn_unique` (`isbn`),
  KEY `books_category_id_foreign` (`category_id`),
  CONSTRAINT `books_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `books`
--

LOCK TABLES `books` WRITE;
/*!40000 ALTER TABLE `books` DISABLE KEYS */;
INSERT INTO `books` VALUES
(1,1,'978-1569319017','One Piece Vol. 1: Romance Dawn','Eiichiro Oda','Shueisha / Viz Media',1997,'ambos','Monkey D. Luffy zarpa al Grand Line en busca del legendario tesoro One Piece. El manga más vendido de la historia en edición física y digital de alta definición.','https://covers.openlibrary.org/b/isbn/9781569319017-L.jpg','https://mangaplus.shueisha.co.jp/titles/200016','Vault Manga M-01 · Streaming HD',6,5,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(2,1,'978-1401207526','Batman: Año Uno (Deluxe Edition)','Frank Miller & David Mazzucchelli','DC Comics Black Label',1987,'ambos','La obra maestra noir que redefinió el origen de Bruce Wayne como el Caballero Oscuro y la alianza con James Gordon en las calles de Gotham.','https://covers.openlibrary.org/b/isbn/9781401207526-L.jpg','https://openlibrary.org/isbn/9781401207526','Vault Cómics DC-01',4,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(3,1,'978-1401245252','Watchmen (Absolute Collector)','Alan Moore & Dave Gibbons','DC Comics / Vertigo',1986,'digital','¿Quién vigila a los vigilantes? En un mundo al borde del colapso nuclear, una conspiración contra antiguos superhéroes cambia la historia del cómic para siempre.','https://covers.openlibrary.org/b/isbn/9781401245252-L.jpg','https://openlibrary.org/isbn/9781401245252','NovaEther Cloud Comic',5,5,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(4,1,'978-1569319000','Naruto Vol. 1: Uzumaki Naruto','Masashi Kishimoto','Shueisha / Panini Manga',1999,'ambos','El inicio de la leyenda ninja de Naruto Uzumaki en la Aldea Oculta de la Hoja y su camino indomable para convertirse en Hokage.','https://covers.openlibrary.org/b/isbn/9781569319000-L.jpg','https://mangaplus.shueisha.co.jp/','Vault Manga M-02',4,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(5,1,'978-1612620244','Attack on Titan (Shingeki no Kyojin) Vol. 1','Hajime Isayama','Kodansha Comics',2009,'ambos','La humanidad sobrevive tras enormes murallas hasta que la aparición del Titán Colosal desata una batalla épica por la libertad.','https://covers.openlibrary.org/b/isbn/9781612620244-L.jpg','https://openlibrary.org/isbn/9781612620244','Vault Manga M-03',1,0,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(6,2,'978-8497592208','Cien años de soledad','Gabriel García Márquez',' Vintage Español / Debolsillo',1967,'ambos','La saga inmortal de la familia Buendía en el mítico pueblo de Macondo. Una joya indispensable de la literatura mundial.','https://covers.openlibrary.org/b/isbn/9788497592208-L.jpg','https://www.cervantesvirtual.com/','Salón Dorado A-01',5,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(7,2,'978-8420412146','La ciudad y los perros (Edición Conmemorativa)','Mario Vargas Llosa','Alfaguara',1963,'fisico','La novela ganadora del Premio Biblioteca Breve que catapultó el boom latinoamericano con una narrativa feroz e inolvidable.','https://covers.openlibrary.org/b/isbn/9788420412146-L.jpg',NULL,'Salón Dorado A-02',1,0,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(8,3,'978-0441172719','Dune (Crónicas de Arrakis)','Frank Herbert','Ace Sci-Fi / Nova',1965,'ambos','En el desértico planeta Arrakis, Paul Atreides lidera una rebelión interestelar por el recurso más valioso del universo: la especia melange.','https://covers.openlibrary.org/b/isbn/9780441172719-L.jpg','https://openlibrary.org/isbn/9780441172719','Sector Sci-Fi B-01',4,3,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(9,3,'978-0451524935','1984 (Edición Definitiva)','George Orwell','Signet Classics',1949,'ambos','La distopía más influyente del siglo XX: vigilancia total, manipulación de la verdad y la rebelión de Winston Smith contra el Gran Hermano.','https://covers.openlibrary.org/b/isbn/9780451524935-L.jpg','https://openlibrary.org/isbn/9780451524935','Sector Sci-Fi B-04',5,5,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(10,4,'978-0132350884','Clean Code: Arquitectura y Código Limpio','Robert C. Martin','Prentice Hall Tech',2008,'digital','El manual imprescindible para ingenieros de software y arquitectos digitales que buscan excelencia técnica en cada línea de código.','https://covers.openlibrary.org/b/isbn/9780132350884-L.jpg','https://openlibrary.org/isbn/9780132350884','NovaEther Tech Cloud',6,5,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(11,4,'978-0201633610','Design Patterns: Patrones de Software Moderno','Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides','Addison-Wesley Professional',1994,'ambos','Los 23 patrones de diseño esenciales que sustentan los frameworks y sistemas empresariales más potentes del mundo.','https://covers.openlibrary.org/b/isbn/9780201633610-L.jpg','https://openlibrary.org/isbn/9780201633610','Sector Tech T-02',4,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(12,5,'978-0345450661','El psicoanalista','John Katzenbach','Ballantine Books / Ediciones B',2002,'ambos','«Feliz 53 cumpleaños, doctor. Bienvenido al primer día de su muerte». Un juego de ajedrez psicológico que no podrás soltar.','https://covers.openlibrary.org/b/isbn/9780345450661-L.jpg','https://openlibrary.org/isbn/9780345450661','Sector Noir C-02',3,3,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(13,6,'978-1612680194','Padre Rico, Padre Pobre','Robert T. Kiyosaki','Plata Publishing',1997,'ambos','El bestseller global sobre libertad financiera, creación de activos, inversión inteligente y mentalidad emprendedora.','https://covers.openlibrary.org/b/isbn/9781612680194-L.jpg','https://openlibrary.org/isbn/9781612680194','Business Hub E-01',5,5,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(14,7,'978-0735211292','Hábitos Atómicos','James Clear','Avery / Penguin Random House',2018,'ambos','El método definitivo respaldado por la neurociencia para construir hábitos extraordinarios y alcanzar el máximo potencial.','https://covers.openlibrary.org/b/isbn/9780735211292-L.jpg','https://openlibrary.org/isbn/9780735211292','Mindset Lounge F-03',4,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(15,8,'978-0062316097','Sapiens: De animales a dioses','Yuval Noah Harari','Harper / Debate',2014,'ambos','Cómo nuestra especie logró dominar el planeta, crear imperios, dinero, ciencia e inteligencia artificial.','https://covers.openlibrary.org/b/isbn/9780062316097-L.jpg','https://openlibrary.org/isbn/9780062316097','Galería Historia D-01',4,4,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(16,9,'978-0156012195','El Principito (Edición Ilustrada)','Antoine de Saint-Exupéry','Harvest / Salamandra',1943,'ambos','«Lo esencial es invisible a los ojos». Una obra atemporal con sus acuarelas originales restauradas.','https://covers.openlibrary.org/b/isbn/9780156012195-L.jpg','https://openlibrary.org/isbn/9780156012195','Zona Aventura G-01',6,6,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(17,10,'978-0714832470','La Historia del Arte (Edición Coleccionista)','E. H. Gombrich','Phaidon Press',1950,'fisico','El libro de arte y estética visual más célebre del mundo en formato de lujo.','https://covers.openlibrary.org/b/isbn/9780714832470-L.jpg',NULL,'Galería Visual H-02',2,2,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(18,11,'978-1108457651','English Grammar in Use (5th Global Edition)','Raymond Murphy','Cambridge Press',2019,'digital','El bestseller mundial para dominar el inglés profesional e internacional de forma autodidacta.','https://covers.openlibrary.org/b/isbn/9781108457651-L.jpg','https://openlibrary.org/isbn/9781108457651','NovaEther Global Cloud',8,8,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(19,12,'978-1451648539','Steve Jobs: La Biografía Oficial','Walter Isaacson','Simon & Schuster / Debate',2011,'ambos','El retrato definitivo sobre innovación, diseño obsesivo y liderazgo tecnológico del fundador de Apple y Pixar.','https://covers.openlibrary.org/b/isbn/9781451648539-L.jpg','https://openlibrary.org/isbn/9781451648539','Salón Pioneros I-01',3,3,'2026-11-11 02:44:12','2026-11-11 02:44:12');
/*!40000 ALTER TABLE `books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `icono` varchar(30) NOT NULL DEFAULT '?',
  `descripcion` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_nombre_unique` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'Cómics, Manga y Novela Gráfica','🎌','Manga shonen/seinen, cómics de superhéroes y novelas gráficas de colección.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(2,'Literatura y Ficción Bestseller','📖','Grandes novelas contemporáneas, éxitos de ventas y clásicos universales.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(3,'Ciencia Ficción, Fantasía y Terror','🚀','Mundos distópicos, sagas épicas, viajes intergalácticos y horror sobrenatural.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(4,'Tecnología, IA y Desarrollo (Tech)','💻','Ingeniería de software, inteligencia artificial, arquitectura cloud y ciencia.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(5,'Misterio, Thriller y Policial','🕵️','Novela negra, suspenso psicológico, crímenes y casos de investigación.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(6,'Negocios, Finanzas y Startups','📈','Inversiones, mentalidad empresarial, liderazgo, cripto y marketing digital.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(7,'Desarrollo Personal y Mindset','🌱','Productividad de alto rendimiento, hábitos, psicología práctica y bienestar.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(8,'Historia, Filosofía y Sociedad','🏛️','Civilizaciones, pensamiento estratégico, geopolítica y crónicas de la humanidad.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(9,'Juvenil y Sagas de Aventura (YA)','⚡','Fantasía juvenil, relatos ilustrados y sagas fenómeno a nivel mundial.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(10,'Arte, Cine, Diseño y Cultura Pop','🎨','Concept art, dirección cinematográfica, fotografía, música y arquitectura.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(11,'Idiomas y Guías Internacionales','🌍','Dominio de inglés, japonés, certificaciones globales y manuales prácticos.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(12,'Biografías de Íconos y Pioneros','🖋️','La mente detrás de los grandes fundadores, artistas, científicos y leyendas.','2026-11-11 02:44:12','2026-11-11 02:44:12');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `book_id` bigint(20) unsigned NOT NULL,
  `reader_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `fecha_prestamo` date NOT NULL,
  `fecha_devolucion_esperada` date NOT NULL,
  `fecha_devolucion_real` date DEFAULT NULL,
  `estado` enum('prestado','devuelto','vencido') NOT NULL DEFAULT 'prestado',
  `observaciones` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `loans_book_id_foreign` (`book_id`),
  KEY `loans_reader_id_foreign` (`reader_id`),
  KEY `loans_user_id_foreign` (`user_id`),
  CONSTRAINT `loans_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`),
  CONSTRAINT `loans_reader_id_foreign` FOREIGN KEY (`reader_id`) REFERENCES `readers` (`id`),
  CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES
(1,1,1,2,'2026-11-07','2026-11-14',NULL,'prestado','Entrega de edición coleccionista + acceso a lectura digital HD.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(2,7,2,1,'2026-10-31','2026-11-08',NULL,'vencido','Última unidad en circulación. Stock actual agotado (0 disponibles).','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(3,5,3,2,'2026-11-05','2026-11-12',NULL,'prestado','Reserva exclusiva de tomo manga.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(4,10,4,2,'2026-11-09','2026-11-16',NULL,'prestado','Licencia Cloud activada para cliente.','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(5,13,1,1,'2026-10-29','2026-11-05','2026-11-04','devuelto','Devolución completada con éxito.','2026-11-11 02:44:13','2026-11-11 02:44:13');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2026_11_10_000001_create_biblioteca_tables',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `readers`
--

DROP TABLE IF EXISTS `readers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `readers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dni_codigo` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo` enum('estudiante','docente','publico') NOT NULL DEFAULT 'estudiante',
  `estado` enum('activo','suspendido') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `readers_dni_codigo_unique` (`dni_codigo`),
  UNIQUE KEY `readers_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `readers`
--

LOCK TABLES `readers` WRITE;
/*!40000 ALTER TABLE `readers` DISABLE KEYS */;
INSERT INTO `readers` VALUES
(1,'72849102','Carlos Alberto','Mendoza Ríos','cliente@novaether.com','987654321','estudiante','activo','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(2,'74102938','Lucía Fernanda','Quispe Salazar','lucia.quispe@outlook.com','912345678','docente','activo','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(3,'41928374','Roberto','Paredes Vargas','rparedes@novaether.club','998877665','docente','activo','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(4,'76543210','Andrea Valeria','Rojas Castillo','andrea.rojas@gmail.com','955443322','publico','activo','2026-11-11 02:44:12','2026-11-11 02:44:12'),
(5,'70918273','Diego Alejandro','Torres Huamán','diego.torres@hotmail.com','944332211','publico','suspendido','2026-11-11 02:44:12','2026-11-11 02:44:12');
/*!40000 ALTER TABLE `readers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rol` enum('admin','bibliotecario','cliente') NOT NULL DEFAULT 'cliente',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Director Ejecutivo · NovaEther','admin@novaether.com','admin',NULL,'$2y$12$iB4wAsXBAXBeeSjfyB99xO1sqA8slhx5WXx.U3KABXzWPxI6z.Clu',NULL,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(2,'Especialista de Catálogo & Reservas','staff@novaether.com','bibliotecario',NULL,'$2y$12$aQeKKCWMQKFyYZpovl74Ted7jsgAKjsF6yFO1aNn5zcAn3Z1YvE7i',NULL,'2026-11-11 02:44:12','2026-11-11 02:44:12'),
(3,'Carlos Alberto Mendoza Ríos','cliente@novaether.com','cliente',NULL,'$2y$12$vKTIMXsSqwGZfsy2g.ETBO1NPcy0C/ul9c9SGKVSQ8ZlQLML9SjS.',NULL,'2026-11-11 02:44:12','2026-11-11 02:44:12');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-10 16:44:13
