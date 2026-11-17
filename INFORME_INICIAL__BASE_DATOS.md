# Informe de la base de datos `biblioteca_bd`

## 1. Resumen general

La base de datos `biblioteca_bd` utiliza MariaDB/MySQL mediante XAMPP, codificación `utf8mb4` y motor InnoDB en sus tablas principales. Está diseñada para una biblioteca virtual que combina:

- Registro y autenticación de usuarios.
- Catálogo de libros y categorías.
- Préstamos físicos o digitales.
- Venta y alquiler de ejemplares.
- Suscripciones y puntos Karma.
- Reseñas, favoritos y lista de espera.
- Reclamos, respuestas administrativas y tokens API.

El esquema se crea mediante las migraciones de Laravel ubicadas en `database/migrations/`. Los datos iniciales se cargan mediante `database/seeders/DatabaseSeeder.php`.

## 2. Estado actual

| Tabla | Función principal | Filas aproximadas |
|---|---|---:|
| `users` | Cuentas y roles del sistema | 2 |
| `readers` | Perfil bibliotecario del usuario | 5 |
| `categories` | Categorías del catálogo | 12 |
| `books` | Catálogo, stock y precios | 19 |
| `loans` | Préstamos, devolución y pago | 5 |
| `waitlists` | Solicitudes de libros agotados | 1 |
| `reviews` | Calificaciones y comentarios | 8 |
| `favorites` | Libros favoritos por usuario | 3 |
| `karma_movements` | Movimientos del saldo Karma | 2 |
| `complaints` | Reclamos y quejas | 1 |
| `orders` | Órdenes de venta o alquiler | 0 |
| `order_items` | Productos o ítems de una orden | 0 |
| `api_tokens` | Acceso de aplicaciones móviles/API | 0 |
| `sessions` | Sesiones web | 1 |
| `cache`, `cache_locks` | Caché administrada por Laravel | 2 |
| `jobs`, `failed_jobs`, `job_batches` | Cola y trabajos fallidos | 0 |
| `migrations` | Historial de migraciones ejecutadas | 7 |
| `password_reset_tokens` | Recuperación de contraseñas | 0 |

## 3. Modelo de relaciones

### Usuarios y lectores

`users` centraliza la autenticación y contiene:

- Nombre y correo único.
- Contraseña.
- Rol: `admin` o `cliente`.
- Fecha de verificación.
- Token de sesión persistente.

`readers` representa la ficha del lector y tiene una relación uno a uno con `users` mediante `user_id`.

Cada lector puede tener:

- Varios préstamos: `readers -> loans`.
- Varias órdenes: `readers -> orders`.
- Varias solicitudes de espera: `readers -> waitlists`.
- Un historial de puntos Karma: `readers -> karma_movements`.

La baja de un usuario elimina su ficha del lector; la relación utiliza `ON DELETE CASCADE` o configuración de Laravel según la tabla.

### Catálogo

`categories` agrupa los libros. Cada categoría puede contener muchos libros, mientras que cada libro pertenece a una sola categoría.

Relación:

```text
categories (1) ----< (N) books
```

`books` almacena ISBN, título, autor, editorial, formato, sinopsis, portada, ubicación física, stock, precio de venta y precio de alquiler.

El ISBN es único y `category_id` impide asociar un libro con una categoría inexistente. Eliminación del catálogo: la categoría no puede eliminarse si tiene libros asociados (`restrict`).

### Préstamos

`loans` relaciona simultáneamente:

- Un libro.
- Un lector.
- El usuario que realizó la operación.

Relaciones:

```text
books (1) ----< (N) loans
readers (1) ----< (N) loans
users (1) ----< (N) loans
```

También controla:

- Fecha de préstamo y vencimiento.
- Fecha real de devolución.
- Estado: prestado, devuelto o vencido.
- Método y estado de pago.
- Código de operación.
- Fechas de pago y expiración.
- Observaciones.

Las restricciones impiden eliminar libros, lectores o usuarios que tengan préstamos asociados.

### Órdenes y detalle de compra

`orders` registra una compra o alquiler para un usuario y opcionalmente para un lector.

```text
users (1) ----< (N) orders
readers (1) ----< (N) orders
orders (1) ----< (N) order_items
```

Cada `order_item` identifica el libro vendido o alquilado y puede vincularse al préstamo generado.

```text
books (1) ----< (N) order_items
loans (1) ----< (N) order_items
```

Un ítem puede representar una venta o un alquiler, cantidad, duración en días, precio unitario y subtotal.

Al eliminar una orden, sus ítems se eliminan en cascada. Un libro no puede eliminarse si tiene ítems asociados. Al eliminar un préstamo, el vínculo desde `order_items.loan_id` se vuelve nulo.

### Reseñas y favoritos

`reviews` conecta usuarios y libros:

```text
users (1) ----< (N) reviews
books (1) ----< (N) reviews
```

Incluye calificación, comentario, indicador de spoilers, visibilidad y moderador. La combinación única `book_id + user_id` permite como máximo una reseña por usuario y libro.

`favorites` implementa la relación muchos a muchos entre usuarios y libros:

```text
users (N) ----< favorites >---- (N) books
```

La combinación única `user_id + book_id` impide duplicar un mismo favorito.

### Lista de espera

`waitlists` conecta un libro agotado con un lector:

```text
books (1) ----< (N) waitlists
readers (1) ----< (N) waitlists
```

Controla si está en espera, notificado, completado o cancelado, y registra cuándo fue notificado.

### Karma

`karma_movements` funciona como libro contable de los puntos asociados a un lector.

```text
readers (1) ----< (N) karma_movements
```

Cada movimiento registra puntos positivos o negativos, motivo y una posible referencia al elemento que produjo el movimiento.

### Reclamos

`complaints` almacena reclamos o quejas de consumidores.

Puede vincularse con un usuario administrador mediante `respondido_por_id`. Esta relación permite conservar el reclamo si el usuario que respondió ya no existe, estableciendo el vínculo en `NULL`.

Campos principales:

- Código único de reclamo.
- Datos del consumidor.
- Tipo de solicitud.
- Detalle y pedido del consumidor.
- Estado de atención.
- Respuesta y administrador responsable.

### Tokens API

`api_tokens` relaciona aplicaciones o dispositivos con un usuario:

```text
users (1) ----< (N) api_tokens
```

El token se almacena como hash SHA-256, tiene una fecha de vencimiento y registra su último uso.

## 4. Integridad y reglas

### Claves foráneas principales

- `books.category_id -> categories.id`
- `loans.book_id -> books.id`
- `loans.reader_id -> readers.id`
- `loans.user_id -> users.id`
- `readers.user_id -> users.id`
- `waitlists.book_id -> books.id`
- `waitlists.reader_id -> readers.id`
- `reviews.book_id -> books.id`
- `reviews.user_id -> users.id`
- `favorites.book_id -> books.id`
- `favorites.user_id -> users.id`
- `orders.user_id -> users.id`
- `orders.reader_id -> readers.id`
- `order_items.order_id -> orders.id`
- `order_items.book_id -> books.id`
- `order_items.loan_id -> loans.id`
- `karma_movements.reader_id -> readers.id`
- `api_tokens.user_id -> users.id`
- `complaints.respondido_por_id -> users.id`
- `reviews.moderado_por_id -> users.id`

### Restricciones únicas

- Correo único en `users`.
- DNI/código único y correo único en `readers`.
- ISBN único en `books`.
- Nombre único en `categories`.
- Código único en `orders` y `complaints`.
- Token único en `api_tokens`.
- Una reseña por usuario y libro.
- Un favorito por usuario y libro.

### Índices de rendimiento

- Stock por categoría y disponibilidad en `books`.
- Disponibilidad independiente en `books`.
- Búsqueda de texto completo por título y autor.
- Estado y fecha esperada de devolución en `loans`.
- Lector y estado en `loans`.
- Estado de pago y expiración en `loans` y `orders`.
- Libro y estado en `waitlists`.
- Lector y estado en `waitlists`.
- Reseñas por libro y calificación.
- Órdenes por usuario y fecha.
- Movimientos Karma por lector y fecha.

## 5. Flujo funcional

```text
Usuario
  ├── tiene una ficha de lector
  │     ├── realiza préstamos
  │     ├── recibe puntos Karma
  │     ├── puede solicitar lista de espera
  │     └── puede generar órdenes
  ├── escribe reseñas
  ├── guarda favoritos
  └── usa tokens API

Categoría
  └── agrupa libros
       ├── están disponibles en préstamos
       ├── pueden venderse o alquilarse
       ├── reciben reseñas y favoritos
       └── generan lista de espera si están agotados

Orden
  └── contiene varios order_items
       ├── venta de libros
       └── alquiler vinculado a un préstamo
```

## 6. Migraciones

Las tablas se construyeron en cuatro etapas principales:

1. Usuarios, caché y trabajos de Laravel.
2. Categorías, libros, lectores y préstamos.
3. Integridad referencial, índices y lista de espera.
4. Comercio, reseñas, favoritos, Karma, reclamos y pagos.
5. Órdenes, ítems, movimientos Karma, tokens API y moderación administrativa.

Archivo SQL histórico incluido en el proyecto: `database/biblioteca_web.sql`.
