# ChopCheck

Aplicación web para dividir la cuenta de una mesa de restaurante entre los comensales. Cada persona entra desde su móvil, marca lo que ha consumido y recibe un PIN para pagar su parte en caja.

Proyecto de 1.º de DAM hecho con PHP sin frameworks.

Capturas y vídeo: [gustavo-delnardo.vercel.app/portfolio/chopcheck](https://gustavo-delnardo.vercel.app/portfolio/chopcheck)

## Cómo funciona

**Comensales (móvil)**
- Entran en la mesa con el código que les da el camarero y escriben su nombre.
- Ven los productos de la mesa y marcan los que han consumido. Si varias personas marcan el mismo producto, el precio se reparte entre ellas.
- Generan un PIN de 4 dígitos para pagar su parte. La página comprueba cada 2 segundos si el pago ya se ha validado.
- Pueden cambiar el idioma entre español e inglés.

**Camarero (panel de administración)**
- Inicia sesión con usuario y contraseña.
- Ve el plano del local por secciones (terraza, salón y barra) y abre o cierra mesas. Al abrir una mesa se genera un código de 6 caracteres; al cerrarla se vacía la cuenta.
- Añade productos desde el catálogo o a mano, y los borra.
- Valida los pagos con el PIN del cliente. El panel comprueba cada 3 segundos si hay pagos pendientes y avisa de qué mesa está lista para pagar.

## Detalles técnicos

- **PHP 8** sin frameworks, con patrón **MVC** y un único punto de entrada (`public/index.php`) que decide qué controlador usar según la ruta.
- **MySQL** con **PDO** y consultas preparadas.
- Contraseñas del panel guardadas con `password_hash` y comprobadas con `password_verify`.
- Los precios se guardan en céntimos (enteros) para evitar errores de redondeo. Cuando un producto se divide, los céntimos que sobran se reparten uno a uno para que la suma cuadre siempre (`dividir_centimos_equitable` en `src/helpers/helpers.php`).
- El total de cada persona se recalcula en el servidor al generar el PIN; no se usa lo que envía el navegador.
- **JavaScript** sin librerías: `fetch` cada pocos segundos para actualizar la mesa y comprobar pagos y avisos.
- **CSS** propio, pensado primero para móvil.

## Estructura

```text
ChopCheck/
├── config/
│   └── database.php.example   # Plantilla de conexión (copiar como database.php)
├── database/
│   ├── schema.sql             # Tablas y datos de prueba (mesas y catálogo)
│   └── crear_admin.php        # Crea un usuario del panel desde la terminal
├── public/                    # Única carpeta que debe servir el servidor web
│   ├── index.php              # Punto de entrada y rutas
│   └── styles.css
├── src/
│   ├── controllers/           # HomeController, MesaController, AdminController
│   ├── models/                # Mesa, Participante, Item, Articulo, Pago, Admin
│   ├── helpers/               # Códigos de mesa, PIN, céntimos, idioma
│   └── lang/                  # Textos en español e inglés
└── views/                     # Vistas del comensal (user) y del camarero (admin)
```

## Instalación en local

Necesitas PHP 8 con la extensión `pdo_mysql` y MySQL o MariaDB.

1. Clona el repositorio:
   ```bash
   git clone https://github.com/soytavodev/ChopCheck.git
   cd ChopCheck
   ```
2. Crea la base de datos e importa el esquema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE chopcheck CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   mysql -u root -p chopcheck < database/schema.sql
   ```
3. Copia la configuración y pon tus datos de MySQL:
   ```bash
   cp config/database.php.example config/database.php
   ```
4. Crea un usuario para el panel del camarero:
   ```bash
   php database/crear_admin.php camarero una-contraseña
   ```
5. Arranca el servidor de PHP:
   ```bash
   php -S localhost:8000 -t public
   ```
6. Abre `http://localhost:8000`. El panel está en `http://localhost:8000/index.php?route=admin_login`.

## Autor

Gustavo Delnardo · [GitHub](https://github.com/soytavodev) · [Web](https://gustavo-delnardo.vercel.app)
