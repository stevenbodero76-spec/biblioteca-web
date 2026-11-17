USE biblioteca_bd;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS reading_logs;   -- se recrea más abajo con user_id opcional
SET FOREIGN_KEY_CHECKS = 1;

DROP INDEX IF EXISTS books_categoria_nivel_idx ON books;
DROP INDEX IF EXISTS books_nivel_idx ON books;
ALTER TABLE books DROP COLUMN IF EXISTS nivel_acceso;

-- Primero order_items (depende de orders y de loans), luego orders
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
SET FOREIGN_KEY_CHECKS = 1;

-- Precios en books
ALTER TABLE books
  DROP COLUMN IF EXISTS precio_venta,
  DROP COLUMN IF EXISTS precio_alquiler;

-- Campos de pago en loans (los índices que solo usaban estas columnas desaparecen solos)
ALTER TABLE loans
  DROP COLUMN IF EXISTS metodo_pago,
  DROP COLUMN IF EXISTS estado_pago,
  DROP COLUMN IF EXISTS codigo_operacion,
  DROP COLUMN IF EXISTS fecha_pago,
  DROP COLUMN IF EXISTS fecha_expiracion;

ALTER TABLE books
  ADD COLUMN IF NOT EXISTS archivo_pdf VARCHAR(500) NULL,
  ADD COLUMN IF NOT EXISTS tipo_enlace ENUM('pdf','embed','externo') NOT NULL DEFAULT 'embed',
  ADD COLUMN IF NOT EXISTS rating_promedio DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  ADD COLUMN IF NOT EXISTS total_resenas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS total_lecturas INT UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;

-- Si archivo_pdf ya existía como TEXT, se reduce a VARCHAR(500)
ALTER TABLE books MODIFY COLUMN archivo_pdf VARCHAR(500) NULL;

CREATE INDEX IF NOT EXISTS books_categoria_deleted_idx ON books (category_id, deleted_at);
CREATE INDEX IF NOT EXISTS books_lecturas_idx ON books (total_lecturas);
CREATE INDEX IF NOT EXISTS books_rating_idx ON books (rating_promedio);

-- Cargar los contadores con los datos actuales
UPDATE books b
LEFT JOIN (
  SELECT book_id, ROUND(AVG(calificacion), 1) AS prom, COUNT(*) AS total
  FROM reviews
  GROUP BY book_id
) r ON r.book_id = b.id
SET b.rating_promedio = COALESCE(r.prom, 0),
    b.total_resenas   = COALESCE(r.total, 0);

-- Tipo de lector: solo estudiante, docente o publico
ALTER TABLE readers MODIFY COLUMN tipo VARCHAR(20) NOT NULL DEFAULT 'publico';
UPDATE readers SET tipo = 'publico' WHERE tipo NOT IN ('estudiante','docente','publico');
ALTER TABLE readers MODIFY COLUMN tipo ENUM('estudiante','docente','publico') NOT NULL DEFAULT 'publico';

-- Saldo Karma guardado
ALTER TABLE readers
  ADD COLUMN IF NOT EXISTS saldo_karma INT NOT NULL DEFAULT 0;

UPDATE readers r
LEFT JOIN (
  SELECT reader_id, SUM(puntos) AS saldo
  FROM karma_movements
  GROUP BY reader_id
) k ON k.reader_id = r.id
SET r.saldo_karma = COALESCE(k.saldo, 0);

-- Borrado lógico en users
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL;

CREATE TABLE IF NOT EXISTS reading_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  book_id BIGINT UNSIGNED NOT NULL,
  leido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY reading_logs_user_leido_idx (user_id, leido_en),
  KEY reading_logs_book_leido_idx (book_id, leido_en),
  CONSTRAINT reading_logs_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
  CONSTRAINT reading_logs_book_fk FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

