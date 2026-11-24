# 🗄️ Evidencias Técnicas · ROL 1 (Base de Datos, Migraciones, Seeders y Modelos)

## 1. Resumen de Tareas Ejecutadas

1. **En `database/seeders/DatabaseSeeder.php`:**
   - Se eliminaron todos los cálculos comerciales de `precio_alquiler`, `precio_venta`, `plan_suscripcion`, `plan_vence_at` y cobros `yape_plin` / `mostrador`.
   - Se asignaron enlaces reales de lectura abierta en `archivo_pdf` a las obras que tenían `null`:
     - *La ciudad y los perros*: `https://openlibrary.org/isbn/9788420412146`
     - *La Historia del Arte*: `https://openlibrary.org/isbn/9780714832470`
   - Se poblaron los campos `idioma` (`es`, `en`) y `licencia` (`dominio_publico`, `acceso_abierto`) en las 18 obras del catálogo.

2. **En `app/Models/Book.php`:**
   - Se retiraron `precio_alquiler`, `precio_venta` y la relación comercial `orderItems()`.
   - Se agregaron `idioma` y `licencia` a `$fillable`.
   - En `scopeFiltro()` se activó la búsqueda rápida `whereFullText(['titulo', 'autor'], $buscar)` para MySQL/MariaDB con soporte de respaldo por `LIKE` para ISBN, editorial y entornos SQLite.

3. **En `app/Models/Reader.php`, `app/Models/Loan.php` y `app/Models/User.php`:**
   - En `Reader.php`: se eliminaron `plan_suscripcion`, `plan_vence_at`, `puedeUsarMembresia()` y `orders()`, y se dejó `etiquetaPlan()` únicamente con categorías de biblioteca pública (`Estudiante`, `Docente`, `Lector Público`).
   - En `Loan.php`: se eliminó `orderItem()` y se simplificó `etiquetaMetodoPago()` a `"Préstamo Público Gratuito"`.
   - En `User.php`: se eliminó la relación comercial `orders()`.

4. **En `app/Models/` y `database/migrations/`:**
   - Se eliminaron físicamente los modelos comerciales `app/Models/Order.php` y `app/Models/OrderItem.php`.
   - Se creó la migración `database/migrations/2026_11_23_000001_add_public_library_fields_to_books_and_readers.php` que hace `dni_codigo` opcional (`nullable`) en `readers` y añade `idioma` y `licencia` (`dominio_publico`, `creative_commons`, `acceso_abierto`) en `books`.

---

## 2. Cómo Ejecutar los Scripts de Evidencia

```powershell
# 1. Ejecutar migraciones y seeder actualizado
php artisan migrate:fresh --seed

# 2. Ejecutar el script de verificación automática del Rol 1
php evidencias_rol1/02_verificar_rol1.php
```
