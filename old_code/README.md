# ChopCheck (MVP con vista Admin)

## Qué cambia en esta versión
- **Vista Usuario** (mesa): igual que antes, pero ya **no muestra IDs** de participante y la cabecera muestra **Mesa N**.
- **Vista Admin**: crear mesas (auto **Mesa 1, Mesa 2...**), ver todas las mesas, abrir vista usuario, **añadir productos** desde un **catálogo** (artículos de prueba con búsqueda), y validar pagos con **PIN**.
- **Catálogo de artículos** (`articulos`): evita teclear precios a mano; permite añadir N unidades de un clic.

## Archivos nuevos
- `admin.php`: listado de mesas, crear mesa, validador de PIN.
- `admin_mesa.php`: gestionar una mesa concreta y añadir productos desde el catálogo.
- `admin_add_item.php`: inserta N unidades del artículo seleccionado en la mesa.
- `schema.sql` / `migrations.sql`: base y migraciones (añade `numero` y `articulos`).

## Instalación / Actualización
1. **Nueva instalación**: importa `schema.sql`.
2. **Si ya tenías el MVP anterior**: importa `migrations.sql` sobre tu BD existente.
3. Ajusta `db.php` con tus credenciales.
4. Sirve la carpeta con Apache/XAMPP/MAMP o:
   ```bash
   php -S localhost:8000 -t chopcheck_mvp_admin
   ```

## Flujo recomendado
- Personal del local → `admin.php` → **Crear mesa** (se asigna número automáticamente) → **Gestionar** → Añadir productos desde catálogo.
- Clientes → `index.php` → Unirse con **código** → Marcar consumos → **Tu consumo** → Generar PIN → Caja valida en `validar_pago.php` o desde `admin_mesa.php`.

---

© 2026 ChopCheck MVP.
