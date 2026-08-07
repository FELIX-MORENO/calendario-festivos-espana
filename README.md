# calendario-festivos-espana

# 📅 Festivos Municipales

Una aplicación web para consultar los días festivos de los municipios de España. Permite a los administradores sincronizar festivos desde una fuente externa y ofrece una API REST para terceros.
Puedes verla en https://diasfestivosmunicipios.free.nf/

# 🛠️ Stack Tecnológico

# Backend

PHP 8.1.x: Lenguaje principal con tipado estricto, POO y características modernas (atributos, enumerados, match).

MySQL 8.0: Base de datos relacional para el almacenamiento de los datos.

PDO (PHP Data Objects): Para la conexión segura a la base de datos con sentencias preparadas, previniendo inyecciones SQL.

Composer: Gestor de dependencias de PHP.

vlucas/phpdotenv: Para la gestión de variables de entorno (.env).

# Frontend

HTML5, CSS3 y JavaScript (ES6+): Para la estructura, estilo e interactividad de la interfaz web.

Bootstrap 5: Framework CSS para un diseño responsive y rápido.

Axios: Cliente HTTP para realizar peticiones AJAX a la API REST desde el frontend.

JSON: Formato estándar para el intercambio de datos en la API.

# Servidor y Entorno

La plicacion se encuentra en un servidor gratuito https://dash.infinityfree.com/

# Herramientas de Desarrollo y Seguridad

Git: Control de versiones.

GitHub: Alojamiento del repositorio.

Composer: Gestión de dependencias de PHP.

## 📋 Descripción

**Gestión de Festivos Municipales** es una aplicación full-stack que centraliza el calendario laboral de España a nivel nacional, autonómico y municipal.

La aplicación permite:

- 👁️ **Vista pública**: Consultar los festivos de cualquier municipio y año sin necesidad de autenticación.
- 🔄 **Sincronización automática**: Importar festivos desde la API pública de [Calendarios Nacionales](https://calendariosnacionales.com/es/api/).
- 🚀 **API REST**: Exponer los datos de festivos para que otras aplicaciones los consuman de forma segura con autenticación por API Key.

## ✨ Características Implementadas

### ✅ Sincronización de datos desde API externa

- **Origen de datos**: La aplicación consume la API pública de `calendariosnacionales.com`, que proporciona los calendarios laborales oficiales de España.
- **Estructura completa**: Importa festivos nacionales, autonómicos, provinciales y locales para cualquier año.
- **Normalización**: Relaciona comunidades autónomas, provincias y municipios mediante sus códigos oficiales para mantener la integridad referencial.
- **Proceso de importación**: El administrador puede sincronizar los festivos de un año específico mediante un script que limpia y actualiza las tablas afectadas (festivos, municipios, provincias, comunidades).

### ✅ Gestión de datos maestros

- **Municipios**: Almacenados con su código INE y relación con provincias y comunidades autónomas.
- **Festivos**: Guardados como fechas concretas con su tipo (Nacional, Autonómico, Provincial, Local) y año.
- **Auditoría completa**: Todas las tablas incluyen campos de control (`activo`, `creado_por`, `creado_en`, `modificado_por`, `modificado_en`) para trazabilidad.

### ✅ API REST propia

- **Autenticación**: Basada en API Key con un dominio permitido por clave.
- **Rate limiting**: Control de peticiones para prevenir abusos (configurable).
- **Endpoints públicos**: Consulta de festivos por municipio/año y listado de municipios.
- **Seguridad**: Los IDs de los recursos se transmiten encriptados para evitar manipulación.

### ✅ Sistema de Logs y Auditoría

- **Registro de peticiones**: Todas las llamadas a la API se registran con detalles de la solicitud y la respuesta.
- **Logs de autenticación**: Se registran intentos de acceso con API Key, incluyendo éxito/fracaso, IP y origen.
- **Rotación horaria**: Los logs se organizan en archivos por hora para facilitar la gestión.

## 📁 Estructura del Proyecto (MVC)

calendario-festivos-espana/
├── app/
│ ├── Controllers/ # Controladores (ApiController, PublicController, etc.)
│ ├── Models/ # Modelos (FestivoModel, MunicipioModel, ApiKeyModel, etc.)
│ ├── Views/ # Plantillas HTML (públicas)
│ ├── Services/ # Servicios (LoggerService, SyncService, etc.)
│ ├── Helpers/ # Funciones auxiliares (SecurityHelper, etc.)
│ ├── Middleware/ # Middlewares (ApiAuthMiddleware, etc.)
│ ├── Core/ # Clases base (Model, Controller, etc.)
│ └── Exceptions/ # Excepciones personalizadas
├── config/ # Configuración de base de datos y rutas
├── public/ # DocumentRoot (assets, index.php, .htaccess)
├── database/ # Migraciones y scripts de importación
├── logs/ # Archivos de logs (SQL, autenticación)
├── tests/ # Pruebas unitarias y funcionales
├── .env # Variables de entorno (NO subir a Git)
├── .gitignore
└── README.md

## 🔄 Flujo de Usuario

### Vista Pública (Sin autenticación)

1. El usuario accede a la página principal.
2. Selecciona un municipio y un año en los desplegables.
3. Al hacer clic en "Consultar", se realiza una petición AJAX a la API REST.
4. La API devuelve los festivos de ese municipio para el año seleccionado (incluyendo nacionales, autonómicos, provinciales y locales).
5. Los resultados se muestran de forma estructurada, agrupados por nivel.

## 🗄️ Modelo de Datos (Tablas Principales)

- **`comunidades_autonomas`**: Comunidades autónomas con su código oficial.
- **`provincias`**: Provincias relacionadas con su comunidad.
- **`municipios`**: Municipios con código INE y relación con provincia.
- **`festivos`**: Días festivos con fecha, nombre, tipo y relación con la entidad geográfica correspondiente.
- **`api_keys`**: Claves de API con dominio permitido, estado y fecha de expiración.
- **`api_requests`**: Registro de todas las peticiones a la API REST para auditoría y rate limiting.

## 🚀 Endpoints de la API REST

Todos los endpoints requieren el header `X-API-Key`. Además, el dominio desde el que se realiza la petición (`Origin`) debe coincidir con el registrado en la base de datos para esa clave.

### Públicos

#### Obtener festivos de un municipio

GET /api/v1/festivos?municipio_id=ENCRIPTADO&anio=2026

#### Listar municipios

GET /api/v1/municipios

#### Buscar municipios por nombre

GET /api/v1/municipios/buscar?term=Mad

#### Respuestas exitosas

/api/v1/festivos (Lista de festivos)
{
"data": [
{
"id": 1,
"nombre": "Año Nuevo",
"fecha": "2026-01-01",
"tipo": "Nacional",
"nivel": "Nacional"
},
{
"id": 2,
"nombre": "Reyes",
"fecha": "2026-01-05",
"tipo": "Nacional",
"nivel": "Nacional"
}
]
}

/api/v1/municipios (Lista de municipios)
{
"data": [
{
"id": "ENCRIPTADO",
"nombre": "Madrid",
"codigo_ine": "28079"
},
{
"id": "ENCRIPTADO",
"nombre": "Barcelona",
"codigo_ine": "08019"
}
]
}

#### Errores HTTP

401 Unauthorized: API Key inválida o no proporcionada.
403 Forbidden: Dominio no autorizado para la API Key.
429 Too Many Requests: Límite de peticiones excedido.
500 Internal Server Error: Error interno del servidor.

🛠️ Tecnologías Utilizadas
Backend
PHP 8.1+: Con tipado estricto y POO.
MySQL 8.0: Base de datos relacional.
PDO: Conexión segura con sentencias preparadas.
vlucas/phpdotenv: Gestión de variables de entorno.
Monolog: Sistema de logs estructurados.

Frontend
HTML5, CSS3, JavaScript (ES6+)
Bootstrap 5: Para el diseño responsive.
Axios: Para peticiones AJAX a la API REST.
JSON: Formato de intercambio de datos en la API.

Arquitectura
Patrón MVC: Modelo-Vista-Controlador.
Middleware: Capas de seguridad (autenticación, rate limiting, CSRF).
Servicios: Separación de lógica de negocio (sincronización, logs).

🚀 Instalación y Configuración
Requisitos previos
PHP 8.1+ con extensiones pdo_mysql, mysqli, curl, json, mbstring.
MySQL 8.0+.
Composer.
Servidor web (Apache 2.4+ con mod_rewrite).

Instalación
Clonar el repositorio:

git clone https://github.com/tu-usuario/calendario-festivos-espana.git
cd calendario-festivos-espana
Crear la estructura de carpetas:

# En Windows (Laragon)

create_folders.bat
Instalar dependencias de PHP:

composer install
Configurar el entorno:
Copia el archivo de ejemplo y renómbralo:

cp .env.example .env
Edita el archivo .env con tus credenciales de base de datos y la URL de la aplicación:

env (dejo un .env demostracion)
APP_URL=https://tudominio.com
DB_HOST=localhost
DB_NAME=gestion_festivos
DB_USER=usuario
DB_PASSWORD=contraseña
API_KEY=TU_CLAVE_API

Crear la base de datos:
mysql -u usuario -p < database/migrations/01_create_tables.sql

Configurar el VirtualHost:
Asegúrate de que el DocumentRoot apunte a la carpeta public/ del proyecto.

Importar los datos iniciales (municipios y festivos):
php database/imports/sync_festivos.php
Configurar la clave de encriptación (opcional pero recomendado):
Añade ENCRYPTION_KEY a tu archivo .env para encriptar los IDs en la API.

env
ENCRYPTION_KEY=tu_clave_secreta_aqui_32_caracteres

🤝 Contribuir
Si deseas contribuir, por favor:

Haz un fork del repositorio.
Crea una rama para tu funcionalidad (git checkout -b feature/nueva-funcionalidad).
Realiza tus cambios siguiendo el estilo de código PSR-12.
Escribe pruebas para tu código.
Haz commit de tus cambios (git commit -m "Añade nueva funcionalidad").
Sube tu rama (git push origin feature/nueva-funcionalidad).
Abre un Pull Request.

📄 Licencia
Este proyecto está bajo la licencia MIT.

👤 Autor
Desarrollador - FELIX MIGUEL MORENO POLO (https://github.com/FELIX-MORENO)

🙏 Agradecimientos
API de calendarios nacionales: https://calendariosnacionales.com por proporcionar los datos oficiales de festivos.

Comunidad open-source: Por las herramientas que hacen posible este proyecto.
