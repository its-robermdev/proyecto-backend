# C3 Events API

API REST en Laravel para la gestión de eventos tecnológicos del Competitive Coding Club (C3).

## Descripción general

C3 Events API centraliza la gestión operativa y pública de eventos tecnológicos organizados por el C3. El proyecto resuelve la necesidad de administrar eventos desde backoffice, configurar formularios dinámicos de inscripción y habilitar un flujo público controlado para recibir postulaciones o registros.

La solución cubre tres flujos principales:

- Gestión interna de eventos, incluyendo creación, edición, publicación, restauración y cambios de estado;
- Configuración de formularios dinámicos de inscripción asociados a cada evento;
- Registro público de submissions, tanto individuales como por equipo, con validaciones dinámicas y control de cupos.

Los actores que interactúan con el sistema son:

- `admin`: gestiona usuarios, eventos, formularios, moderadores y revisión de submissions;
- `moderator`: accede únicamente a los eventos y submissions donde ha sido asignado;
- `guest`: consulta eventos publicados, ve formularios públicos activos y crea submissions.

## Objetivo del proyecto

Diseñar una API REST en Laravel para la gestión de eventos tecnológicos del C3, permitiendo administrar eventos, configurar procesos de inscripción y registrar participantes de manera segura, estructurada y coherente con las reglas del negocio.

## Funcionalidades principales

### Autenticación y perfil

- Inicio y cierre de sesión mediante tokens de Laravel Sanctum;
- Consulta del perfil autenticado;
- Bloqueo de login para cuentas inactivas o eliminadas lógicamente.

### Gestión de eventos

- Creación de eventos en estado inicial `draft`;
- Edición de metadata del evento;
- Publicación, cierre, cancelación, archivado y restauración según reglas del ciclo de vida;
- Consulta pública de eventos publicados y consulta privada según permisos.

### Formularios de inscripción

- Almacenamiento de formularios dinámicos en `form_schema`;
- Validación estructural del formulario antes de activarlo;
- Activación y desactivación del formulario por evento;
- Acceso público solo cuando el evento está publicado y el formulario está activo.

### Moderadores por evento

- Asignación y remoción de moderadores por evento;
- Visibilidad restringida para moderadores solo sobre eventos donde están asignados.

### Submissions

- Creación pública de submissions individuales o por equipo;
- Almacenamiento de respuestas dinámicas del formulario;
- Gestión de miembros en submissions grupales;
- Revisión administrativa o por moderadores autorizados;
- Restauración y eliminación lógica de submissions.

### Usuarios, roles y permisos

- Alta, edición, activación, desactivación, eliminación lógica y restauración de usuarios;
- Asignación y sincronización de roles;
- Separación entre `admin`, `moderator` y `root admin` mediante `is_root` y políticas de acceso.

## Tecnologías utilizadas

| Categoría | Tecnología | Uso en el proyecto |
| --- | --- | --- |
| Lenguaje | PHP 8.3+ | Runtime principal de la API |
| Framework | Laravel 13 | Base de la aplicación, routing, Eloquent, requests, resources y policies |
| Autenticación | Laravel Sanctum | Tokens personales para autenticación de API |
| Autorización | Spatie Laravel Permission | Gestión de roles y permisos |
| Documentación | Dedoc Scramble | Generación de documentación OpenAPI y UI interactiva |
| Testing | PHPUnit 12 | Tests feature y unit |
| Build tooling | Vite | Pipeline frontend incluido en el scaffold del proyecto |
| Gestión de dependencias | Composer / npm | Instalación de dependencias PHP y frontend |

Nota: este README prioriza PostgreSQL para el levantamiento local. A pesar de esto, el proyecto también incluye configuración para otros drivers como SQLite, MySQL, MariaDB y SQL Server.

## Arquitectura y decisiones técnicas relevantes

La aplicación sigue una organización típica de Laravel, separando responsabilidades por capa para mantener el código defendible, testeable y fácil de extender.

### Capa HTTP

- `Controllers` exponen los endpoints y orquestan la respuesta HTTP;
- `Form Requests` concentran validación de entrada y parte de la validación contextual;
- `Resources` estandarizan la salida JSON.

### Capa de dominio y reglas de negocio

Los `Services` encapsulan reglas de negocio relevantes. En el código actual destacan:

- Ciclo de vida de eventos;
- Activación y validación de formularios;
- Envío de submissions;
- Revisión de submissions;
- Gestión administrativa de usuarios.

### Persistencia

- `Models` de Eloquent representan el dominio persistente;
- `Migrations` versionan el esquema;
- `Factories` y `Seeders` facilitan datos de prueba y ambientes locales.

### Seguridad y acceso

- Laravel Sanctum resuelve autenticación token-based sin introducir una capa externa innecesaria para este contexto académico;
- Spatie Permission implementa RBAC para permisos reutilizables;
- `Policies` de Laravel aplican reglas finas por recurso, especialmente sobre eventos, submissions y usuarios.

Esta combinación es coherente con una API REST académica porque separa autenticación, autorización y negocio sin acoplarlas de forma rígida al controlador.

## Modelo de negocio resumido

| Entidad | Descripción funcional |
| --- | --- |
| `users` | Usuarios internos del sistema. Pueden ser `admin` o `moderator`; además existe el flag `is_root` para privilegios superiores. |
| `events` | Evento tecnológico del C3 con información operativa, estado, cupo, configuración de formulario y responsable creador. |
| `event_moderators` | Relación muchos a muchos entre eventos y usuarios moderadores asignados a su seguimiento. |
| `submissions` | Postulaciones o inscripciones realizadas para un evento. Pueden ser individuales o grupales. |
| `submission_members` | Integrantes de una submission de tipo equipo, incluyendo la marca de capitán. |
| Roles y permisos | Capacidad transversal que define qué recursos puede consultar o modificar cada actor interno. |

Reglas de dominio relevantes detectadas en el código:

- Un evento puede tener múltiples moderadores, pero un moderador solo debería operar sobre eventos donde está asignado;
- El formulario del evento se almacena como estructura JSON en `form_schema`;
- Una submission puede ser `individual` o `team`;
- En submissions de equipo debe existir exactamente un capitán;
- El control de cupos considera submissions en estados `pending` y `approved` como ocupación efectiva;
- Un formulario solo puede usarse públicamente cuando el evento está `published` y `form_is_active` es `true`.

## Seguridad

### Autenticación

La autenticación de la API usa Laravel Sanctum con enfoque token-based. El login expone un token personal que debe enviarse como bearer token en las rutas protegidas.

### Autorización

La autorización combina dos mecanismos:

- Roles y permisos con Spatie Laravel Permission;
- Policies de Laravel para decisiones por recurso.

### Accesos públicos y protegidos

Rutas públicas confirmadas en `routes/api.php`:

- `GET /api/v1/events`
- `GET /api/v1/events/{event}`
- `GET /api/v1/events/{event}/form`
- `POST /api/v1/events/{event}/submissions`

El resto del backoffice opera bajo `auth:sanctum`.

### Reglas de seguridad relevantes del proyecto

- El sistema usa `is_root` para distinguir a un `root admin` con mayor alcance sobre cuentas administrativas;
- El primer usuario creado adquiere automáticamente `is_root`; con el orden actual de `DatabaseSeeder`, `admin@c3.com` queda como root en ambientes sembrados desde cero;
- Un admin no root no puede ver ni gestionar cuentas admin o root ajenas;
- El listado de usuarios para un admin no root solo expone moderadores;
- El root no puede eliminarse, restaurarse, activarse ni desactivarse a sí mismo desde los endpoints administrativos;
- El login devuelve errores explícitos cuando una cuenta está inactiva o eliminada lógicamente;
- La configuración en `bootstrap/app.php` transforma excepciones de autenticación y autorización en respuestas JSON adecuadas para API.

## Requisitos previos

| Requisito | Estado esperado |
| --- | --- |
| PHP | 8.3 o superior |
| Composer | Instalado y disponible en consola |
| PostgreSQL | Instancia disponible para desarrollo local |
| Driver PDO para PostgreSQL | Habilitado en PHP (`pdo_pgsql`) |
| Node.js y npm | Recomendados si se usará Vite o `composer run dev` |
| SQLite driver | Recomendado para ejecutar tests tal como están configurados en `phpunit.xml` |

Nota: `.env.example` viene orientado a SQLite. Para esta guía se asume que ajustarás el entorno para PostgreSQL antes de migrar.

## Instalación y levantamiento local

### 1. Clonar el repositorio

```bash
git clone <URL_DEL_REPOSITORIO>
cd proyecto-backend
```

### 2. Instalar dependencias PHP

```bash
composer install
```

### 3. Crear el archivo de entorno

```bash
cp .env.example .env
```

En Windows PowerShell puedes usar:

```powershell
Copy-Item .env.example .env
```

### 4. Crear una base de datos en PostgreSQL

Crea una base de datos local, por ejemplo:

```sql
CREATE DATABASE c3_events_api;
```

### 5. Ajustar `.env` para PostgreSQL

Edita el archivo `.env` y reemplaza la configuración SQLite por una equivalente a esta:

```env
APP_NAME="C3 Events API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=c3_events_api
DB_USERNAME=postgres
DB_PASSWORD=secret
DB_SSLMODE=prefer
```

Mantén o revisa también estas variables, ya que el proyecto usa almacenamiento basado en base de datos para varias capas del entorno local por defecto:

```env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 6. Generar la app key

```bash
php artisan key:generate
```

### 7. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

Este paso crea el esquema principal del proyecto y datos iniciales para pruebas locales, incluyendo usuarios, roles, permisos, eventos y submissions de ejemplo.

### 8. Instalar dependencias frontend opcionales

Si vas a usar el stack completo de desarrollo con Vite o quieres ejecutar el script `composer run dev`, instala dependencias frontend:

```bash
npm install
```

Para compilar assets una vez:

```bash
npm run build
```

Para vigilar cambios en desarrollo:

```bash
npm run dev
```

### 9. Levantar la API

Modo backend simple:

```bash
php artisan serve
```

Modo de desarrollo completo definido por el proyecto:

```bash
composer run dev
```

Ese script levanta simultáneamente:

- servidor HTTP de Laravel;
- listener de cola con `php artisan queue:listen --tries=1`;
- Vite en modo desarrollo.

### Scripts reales disponibles en el proyecto

```bash
composer run setup
composer run dev
composer run test
```

Observación importante sobre `composer run setup`: el script copia `.env.example` y ejecuta migraciones inmediatamente. Como `.env.example` viene configurado para SQLite, si tu entorno objetivo es PostgreSQL conviene seguir la guía manual anterior o ajustar `.env` antes de reutilizar ese flujo.

## Variables de entorno

| Variable | Propósito | Observación |
| --- | --- | --- |
| `APP_NAME` | Nombre visible de la aplicación | Puede ajustarse para documentación y respuestas locales |
| `APP_ENV` | Entorno de ejecución | Ejemplo: `local`, `production` |
| `APP_KEY` | Clave de cifrado de Laravel | Se genera con `php artisan key:generate` |
| `APP_DEBUG` | Depuración detallada | Debe desactivarse en producción |
| `APP_URL` | URL base de la aplicación | Útil para enlaces generados y documentación |
| `DB_CONNECTION` | Driver de base de datos | Para esta guía: `pgsql` |
| `DB_HOST` | Host de PostgreSQL | Normalmente `127.0.0.1` o el host del contenedor |
| `DB_PORT` | Puerto de PostgreSQL | Por defecto `5432` |
| `DB_DATABASE` | Nombre de la base de datos | Debe existir antes de migrar |
| `DB_USERNAME` | Usuario de base de datos | Según tu instalación local |
| `DB_PASSWORD` | Contraseña de base de datos | Según tu instalación local |
| `DB_SSLMODE` | Modo SSL de PostgreSQL | Soportado por `config/database.php` |
| `QUEUE_CONNECTION` | Backend de colas | `.env.example` usa `database` |
| `CACHE_STORE` | Backend de caché | `.env.example` usa `database` |
| `SESSION_DRIVER` | Driver de sesiones | `.env.example` usa `database` |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios stateful para Sanctum | No aparece en `.env.example`, pero Sanctum lo soporta |
| `API_VERSION` | Versión visible en la documentación OpenAPI | Se toma desde `config/scramble.php` |

## Uso del proyecto

### Credenciales sembradas para desarrollo

Con el orden actual de seeders, estas cuentas quedan disponibles tras `php artisan migrate --seed`:

| Email | Password | Rol principal | Observación |
| --- | --- | --- | --- |
| `admin@c3.com` | `admin123` | `admin` | Se convierte en `root` por ser el primer usuario creado con el seeding actual |
| `adminmini@c3.com` | `adminmini123` | `admin` | Admin no root |
| `mod@c3.com` | `mod123` | `moderator` | Moderador base de pruebas |

### Autenticarse y obtener un token

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@c3.com",
    "password": "admin123"
  }'
```

La respuesta incluye:

- `message`
- `data.token`
- `data.user`
- `status`

### Consumir rutas protegidas

Una vez obtenido el token, envíalo como bearer token:

```bash
curl http://127.0.0.1:8000/api/v1/auth/profile \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <TOKEN>"
```

### Consultar eventos publicados

```bash
curl http://127.0.0.1:8000/api/v1/events \
  -H "Accept: application/json"
```

### Consultar el formulario público de un evento

```bash
curl http://127.0.0.1:8000/api/v1/events/1/form \
  -H "Accept: application/json"
```

El formulario solo estará disponible públicamente si el evento está publicado y `form_is_active` es `true`.

### Registrar una submission pública

```bash
curl -X POST http://127.0.0.1:8000/api/v1/events/1/submissions \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "submitted_by_email": "participant@example.com",
    "submitted_by_name": "Participante C3",
    "participation_type": "individual",
    "form_answers": {
      "portfolio_url": "https://example.com/portfolio",
      "experience_level": "mid",
      "motivation": "Quiero participar y aprender mucho."
    }
  }'
```

Si la submission es de equipo, deben enviarse además `team_name` y `members`, respetando la regla de exactamente un capitán.

### Documentación interactiva

La documentación OpenAPI del proyecto se genera con Scramble y, según la configuración actual, se sirve en:

- `/docs/api`
- `/docs/api.json`

Nota: el acceso a esta documentación depende del middleware de Scramble configurado para el entorno actual.

## Endpoints o módulos principales

Todos los endpoints versionados viven bajo `/api/v1`.

| Módulo | Endpoints principales | Propósito |
| --- | --- | --- |
| Auth | `POST /auth/login`, `POST /auth/logout`, `GET /auth/profile` | Sesión, token y perfil autenticado |
| Events | `GET/POST /events`, `GET/PATCH/DELETE /events/{event}`, `PUT /events/{event}/restore`, `PUT /events/{event}/status` | Gestión y ciclo de vida de eventos |
| Event Form | `GET /events/{event}/form`, `PUT /events/{event}/form`, `POST /events/{event}/form/validation`, `PUT/DELETE /events/{event}/form/activation` | Configuración y exposición del formulario dinámico |
| Event Moderators | `GET/POST /events/{event}/moderators`, `DELETE /events/{event}/moderators/{user}` | Asignación de moderadores por evento |
| Submissions | `POST /events/{event}/submissions`, `GET /events/{event}/submissions`, `GET /submissions/{submission}`, `PATCH /submissions/{submission}/review`, `DELETE /submissions/{submission}`, `PUT /submissions/{submission}/restore` | Registro, revisión y recuperación de postulaciones |
| Users & Roles | `GET/POST /users`, `GET/PATCH/DELETE /users/{user}`, `PUT /users/{user}/restore`, `PUT/DELETE /users/{user}/activation`, `GET/PUT /users/{user}/roles` | Administración de usuarios, activación y roles |

Para el detalle fino de payloads, respuestas y validaciones, consulta la documentación interactiva de Scramble.

## Testing

El proyecto incluye cobertura en dos niveles:

- tests feature para comportamiento HTTP de la API;
- tests unit para servicios, políticas y reglas de negocio.

### Ejecutar tests

```bash
php artisan test
```

O usando el script real del proyecto:

```bash
composer run test
```

### Suites presentes en el repositorio

Feature tests:

- `AuthApiTest`
- `EventApiTest`
- `EventFormApiTest`
- `EventModeratorApiTest`
- `SubmissionApiTest`
- `UserApiTest`

Unit tests:

- `AuthorizationAndModelTest`
- `EventFormServiceTest`
- `EventLifecycleServiceTest`
- `SubmitFormServiceTest`
- `UserManagementServiceTest`

Observación técnica: `phpunit.xml` configura SQLite en memoria para el entorno de pruebas, por lo que los tests no dependen de PostgreSQL para ejecutarse localmente.

## Estructura del proyecto

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Policies/
├── Rules/
├── Services/
└── Support/
bootstrap/
config/
database/
├── factories/
├── migrations/
└── seeders/
routes/
├── api.php
└── web.php
tests/
├── Concerns/
├── Feature/
└── Unit/
```

### Carpeta a carpeta

- `app/Http/Controllers`: endpoints REST y orquestación de respuestas;
- `app/Http/Requests`: validación de entrada y reglas contextuales;
- `app/Http/Resources`: serialización de respuestas JSON;
- `app/Models`: entidades Eloquent del dominio;
- `app/Policies`: autorización por recurso;
- `app/Rules`: reglas de validación reutilizables;
- `app/Services`: lógica de negocio no trivial;
- `app/Support`: catálogos o utilidades compartidas, como permisos;
- `database/migrations`: definición del esquema;
- `database/seeders`: datos iniciales y de soporte local;
- `tests/Feature`: pruebas de endpoints y contratos HTTP;
- `tests/Unit`: pruebas de reglas de negocio y servicios;
- `routes/api.php`: definición de la API versionada.

## Documentación adicional

- Documentación interactiva OpenAPI: `/docs/api`
- Documento OpenAPI exportado: `/docs/api.json`
- DER: `https://drive.google.com/file/d/1oMACIr9pLPas6CuTcgAdDqV5VQtB9sf9/view?usp=sharing`

## Estado del proyecto

Proyecto académico funcional, orientado a evaluación y defensa técnica, con una base backend consistente para evolución posterior. El repositorio ya incorpora autenticación, autorización, reglas de negocio, seeders, documentación OpenAPI y cobertura de tests.

## Autoría o equipo

- Roberto Morán | @its-robermdev
- Chris Marroquín | @ChrisM2309
- Óscar Pleites | @0splei

## Licencia

Uso académico.
