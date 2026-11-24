-- =============================================================================
-- ROL 1 · BASE DE DATOS Y MODELOS (NOVAETHER - BIBLIOTECA DIGITAL 100% GRATUITA)
-- Script SQL de Evidencia: Migración, Licencias Abiertas, Full-Text y Limpieza
-- =============================================================================

-- 1. Hacer dni_codigo opcional (nullable) en la tabla readers para registro sin fricción
ALTER TABLE `readers`
    MODIFY COLUMN `dni_codigo` VARCHAR(20) NULL;

-- 2. Añadir idioma y licencia abierta en la tabla books
ALTER TABLE `books`
    ADD COLUMN `idioma` VARCHAR(10) NOT NULL DEFAULT 'es' AFTER `anio_publicacion`,
    ADD COLUMN `licencia` ENUM('dominio_publico', 'creative_commons', 'acceso_abierto')
        NOT NULL DEFAULT 'acceso_abierto' AFTER `formato`;

-- 3. Asegurar índice FULLTEXT en books(titulo, autor) para búsqueda ultrarrápida en MySQL
-- (Creado en 2026_11_16_000001_upgrade_biblioteca_integrity_and_indexes.php y usado por whereFullText)
-- CREATE FULLTEXT INDEX `idx_books_fulltext_search` ON `books` (`titulo`, `autor`);

-- 4. Completar enlaces reales de visor embebido archive.org/embed/ en obras del catálogo
UPDATE `books`
SET `archivo_pdf` = 'https://archive.org/embed/laciudadylosperr0000varg',
    `formato` = 'ambos'
WHERE `isbn` = '978-8420412146'; -- La ciudad y los perros

UPDATE `books`
SET `archivo_pdf` = 'https://archive.org/embed/the-story-of-art-by-e.-h.-gombrich',
    `formato` = 'ambos'
WHERE `isbn` = '978-0714832470'; -- La Historia del Arte

-- 5. Asignar licencias de dominio público a clásicos universales e idioma inglés a guías internacionales
UPDATE `books`
SET `licencia` = 'dominio_publico'
WHERE `anio_publicacion` <= 1955;

UPDATE `books`
SET `idioma` = 'en'
WHERE `isbn` = '978-1108457651';

-- 6. Normalizar préstamos existentes a modalidad pública 100% gratuita (S/ 0.00)
UPDATE `loans`
SET `metodo_pago` = 'membresia',
    `monto` = 0.00,
    `estado_pago` = 'pagado';
