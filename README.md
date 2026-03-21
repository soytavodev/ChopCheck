🍽️ ChopCheck

ChopCheck es una aplicación web multiplataforma B2B2C diseñada para revolucionar la gestión de cobros en el sector HORECA. Desarrollada por DevNova S.L.

📖 Descripción del Proyecto

ChopCheck soluciona uno de los mayores cuellos de botella en la hostelería: la división de cuentas en mesas de grupos grandes. Mediante un sistema colaborativo en tiempo real, traslada la carga matemática y de gestión del camarero directamente al smartphone del cliente.

El sistema se divide en dos interfaces principales:

Dashboard de Administración (Camareros): Control de mesas activas, gestión de pedidos (CRUD de ítems) y validación de pagos mediante PIN de seguridad.

Interfaz Móvil (Comensales): Acceso vía código QR o alfanumérico. Permite ver el ticket en tiempo real, asignar consumos individuales o fraccionados y generar un código de pago de forma asíncrona.

🚀 Características Técnicas (Stack)

Este proyecto ha sido desarrollado desde cero (sin frameworks de terceros) como Proyecto de Fin de Grado (DAM), garantizando el dominio absoluto de la arquitectura base:

Arquitectura: Patrón MVC (Modelo-Vista-Controlador) con Front Controller (index.php).

Backend: PHP 8+ puro.

Base de Datos: MySQL. Acceso seguro a datos mediante PDO y Prepared Statements para evitar Inyección SQL.

Frontend: HTML5, CSS3 (Variables nativas, Mobile-First) y JavaScript Vanilla.

Tiempo Real: Sistema de sincronización asíncrona mediante Long Polling (API Fetch + setInterval), evitando recargas de página y bloqueos de concurrencia.


⚙️ Instalación y Despliegue en Local

Para levantar este proyecto en tu entorno de desarrollo local (XAMPP, MAMP, o LAMP stack), sigue estos pasos:

Clonar el repositorio:

git clone [https://github.com/soytavodev/chopcheck.git](https://github.com/soytavodev/chopcheck.git)


Configurar la Base de Datos:

Abre tu gestor de base de datos (ej. phpMyAdmin o DBeaver).

Crea una base de datos vacía llamada chopcheck_db (con cotejamiento utf8mb4_unicode_ci).

Importa el archivo db/schema.sql para generar las tablas e insertar los datos de prueba (seeders).

Configurar credenciales (Backend):

Navega hasta src/Config/database.php (o equivalente).

Ajusta las credenciales de PDO si tu usuario no es root o si tienes contraseña en MySQL.

Arrancar el servidor:

Mueve la carpeta del proyecto a tu directorio público (ej. htdocs en XAMPP o /var/www/html).

Accede desde el navegador a: http://localhost/chopcheck/public/

🔒 Seguridad y Buenas Prácticas

Sentencias Preparadas (PDO): Blindaje contra ataques SQLi.

Hashing de contraseñas: Uso de password_hash() y password_verify() (algoritmo BCRYPT) para los accesos del panel de administración.

Control de Concurrencia: Lógica de bloqueo optimista en PHP para evitar que dos usuarios paguen el mismo ítem simultáneamente.

Nomenclatura Estricta: Uso de snake_case para BD, PascalCase para Clases PHP y camelCase para métodos/JS.

👨‍💻 Autor

Desarrollado por Gustavo Delnardo como producto principal de la startup tecnológica DevNova S.L. en el marco del ciclo superior de Desarrollo de Aplicaciones Multiplataforma (DAM).

Este proyecto tiene fines académicos e ilustrativos.
