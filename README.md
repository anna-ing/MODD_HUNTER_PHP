# Gestión de Solicitudes — Mood Hunter

Aplicación tipo "puesto de trabajo" para gestionar **solicitudes** (requests) con un flujo de estados
controlado y generación automática de un **resumen con IA (Google Gemini)** a partir del título y la
descripción de cada solicitud.

Stack: **Laravel 12 (PHP 8.2+) + MySQL + Blade/Alpine.js/Tailwind**, dockerizado con **nginx + PHP-FPM**.

---

## 1. Cómo instalar y ejecutar el proyecto

### Opción A — Docker (recomendado)

Requisito: **Docker Desktop**.

```bash
docker compose up -d --build
```

El primer arranque instala dependencias de Composer, espera a MySQL y ejecuta las migraciones
automáticamente. Servicios disponibles:

| Servicio    | URL / Host                                                    |
| ----------- | ------------------------------------------------------------- |
| Aplicación  | http://localhost:8080/requests                                 |
| Vite (assets) | http://localhost:5173                                        |
| MySQL       | `127.0.0.1:3307` — usuario `mood_hunter`, contraseña `secret`  |

Comandos útiles:

```bash
docker compose logs -f app                 # ver logs
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:rollback
docker compose exec app composer require vendor/paquete
docker compose down                        # detener
docker compose down -v                     # detener y borrar la base de datos
```

### Opción B — Local (sin Docker)

Requisitos: **PHP 8.2+**, **Composer**, **Node 20+**, un servidor MySQL.

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

> También existe el script `composer setup` que automatiza estos pasos.

### Variables de entorno

Definidas en `.env` (copia de `.env.example`). Las relevantes:

| Variable        | Descripción                                                        | Ejemplo                    |
| --------------- | ------------------------------------------------------------------ | -------------------------- |
| `APP_KEY`       | Clave de cifrado de la aplicación (obligatoria).                   | `base64:...`               |
| `APP_ENV`       | Entorno de ejecución.                                              | `local`                    |
| `APP_DEBUG`     | Depuración detallada de errores.                                   | `true`                     |
| `APP_URL`       | URL base de la aplicación.                                         | `http://localhost:8080`    |
| `DB_CONNECTION` | Driver de base de datos.                                           | `mysql`                    |
| `DB_HOST`       | Host de la base de datos (`db` dentro de Docker).                  | `db`                       |
| `DB_PORT`       | Puerto de la base de datos.                                        | `3306`                     |
| `DB_DATABASE`   | Nombre de la base de datos.                                        | `puesto_dev_modd_hunter`   |
| `DB_USERNAME`   | Usuario de la base de datos.                                       | `mood_hunter`              |
| `DB_PASSWORD`   | Contraseña de la base de datos.                                    | `secret`                   |
| `GEMINI_API_KEY`| API Key de Google AI Studio (obligatoria para el resumen con IA).  | `...`                      |
| `GEMINI_MODEL`  | Modelo de Gemini a usar.                                           | `gemini-3.8-flash`         |

En Docker, `DB_*` y `APP_URL` se sobreescriben desde `docker-compose.yml` para apuntar a los servicios
del contenedor; el resto se toma del archivo `.env` montado como volumen.

---

## 2. Documentación de la API

La aplicación expone rutas **web** bajo el prefijo `/requests`. Las acciones de datos responden en
**JSON** (consumidas por Alpine.js con `fetch`); el listado devuelve la **vista HTML**.

Las rutas `requests` y `requests/*` están **excluidas de la verificación CSRF**
(ver `bootstrap/app.php`), por lo que pueden consumirse como API.

### Endpoints

#### `GET /requests`
Carga inicial: renderiza la vista Blade `solicitudes` con el listado completo.

- **Respuesta:** `200 OK` — HTML.

#### `POST /requests`
Crea una nueva solicitud. El estado inicial es siempre `created` y se genera el resumen con IA.

- **Body (JSON):**

```json
{
  "title": "Título de la solicitud",
  "description": "Descripción detallada de la solicitud"
}
```

- **Validación:** `title` requerido, string, máx. 255 · `description` requerido, string.
- **Respuesta `201 Created`:**

```json
{
  "success": true,
  "message": "Solicitud creada correctamente.",
  "data": { "id": 1, "title": "...", "description": "...", "status": "created", "llm_summary": "..." }
}
```

- **Errores:** `422` validación (`{"message","errors"}`) · `422` `{"error","detalle"}`.

#### `POST /requests/{id}`
Actualiza `title` y `description` de una solicitud existente.

- **Body (JSON):** igual que en la creación (ambos campos requeridos).
- **Respuesta `201 Created`:** `{ "success", "message", "data" }`.
- **Errores:** `422` validación · `404` si el `id` no existe.

#### `POST /requests/{id}/transitions`
Ejecuta una transición de estado aplicando las reglas del flujo.

- **Body (JSON):**

```json
{ "status": "in_review" }
```

- **Respuesta `200 OK`:**

```json
{ "mensaje": "Transición ejecutada con éxito.", "data": { "...": "..." } }
```

- **Error `422`:** transición no permitida, p. ej.
  `"No es posible pasar del estado [done] al estado [in_review]."`

### Flujo de estados

Secuencia principal: `created → in_review → in_progress → done`. Desde `in_review` e `in_progress` se
puede pasar a `paused`, y desde `paused` se retoma a `in_progress` o `in_review`.

| Estado        | Puede transicionar a       |
| ------------- | -------------------------- |
| `created`     | `in_review`                |
| `in_review`   | `in_progress`, `paused`    |
| `in_progress` | `done`, `paused`           |
| `paused`      | `in_progress`, `in_review` |
| `done`        | — (estado final)           |

---

## 3. Decisiones de arquitectura

- **MVC + capa de Servicios.** El controlador (`SolicitudController`) solo orquesta y formatea la
  respuesta; la lógica de negocio vive en `SolicitudService`. Esto mantiene el controlador delgado y
  el dominio testable de forma aislada.
- **Máquina de estados como Enum.** `EstadosSolicitud` encapsula los estados y las transiciones
  permitidas (`transicionesPermitidas()` / `puedeTransicionarA()`). Las reglas de negocio quedan en un
  único punto, evitando condicionales dispersos. Los estados inválidos lanzan
  `InvalidArgumentException`, traducida a `422` en el controlador.
- **Form Requests para validación.** `GuardarSolicitud` centraliza las reglas de entrada, desacoplándolas
  del controlador.
- **Servicio de IA aislado.** `LlmService` encapsula por completo la integración con Gemini. Si la IA
  falla, la solicitud **se guarda igualmente** con `llm_summary = null`; la IA nunca bloquea la operación
  principal (degradación elegante).
- **Inyección de dependencias.** `SolicitudService` recibe `LlmService` por constructor y el controlador
  recibe `SolicitudService`, facilitando el testing y el mockeo.
- **Soft Deletes.** El modelo `Solicitud` usa `SoftDeletes`: los registros no se borran físicamente.
- **Frontend sin build obligatorio.** La vista usa **Tailwind y Alpine.js vía CDN**, con acciones
  asíncronas por `fetch` y respuestas JSON, evitando acoplar el renderizado a un pipeline de assets.
- **Entorno reproducible con Docker.** Separación de responsabilidades en contenedores: `nginx`
  (servidor web), `app` (PHP-FPM), `db` (MySQL 8) y `vite` (assets en desarrollo con hot reload).

---

## 4. Estructura del proyecto

```
.
├── app/
│   ├── Enums/
│   │   └── EstadosSolicitud.php      # Estados y reglas de transición (máquina de estados)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── SolicitudController.php  # Endpoints /requests
│   │   └── Requests/
│   │       └── GuardarSolicitud.php     # Validación de title/description
│   ├── Models/
│   │   └── Solicitud.php             # Modelo Eloquent (tabla `requests`, soft deletes)
│   └── Services/
│       ├── SolicitudService.php      # Lógica de negocio de solicitudes
│       └── LlmService.php            # Integración con Google Gemini
├── bootstrap/app.php                 # Configuración de rutas, middleware y CSRF
├── config/                           # Configuración (app, database, gemini, openai, ...)
├── database/
│   ├── factories/                    # Factories de Eloquent
│   ├── migrations/                   # Esquema de la base de datos
│   └── seeders/                      # Seeders
├── docker/
│   ├── nginx/default.conf            # Server block de nginx
│   └── php/entrypoint.sh             # Bootstrap del contenedor (deps, migraciones)
├── resources/
│   ├── css/                          # Tailwind (entrada Vite)
│   ├── js/                           # JS (entrada Vite)
│   └── views/
│       └── solicitudes.blade.php     # Vista principal (Blade + Alpine.js)
├── routes/
│   ├── web.php                       # Rutas /requests
│   └── console.php                   # Comandos de consola
├── storage/                          # Logs, cache, sesiones, vistas compiladas
├── tests/                            # Pruebas (PHPUnit)
├── docker-compose.yml                # Orquestación de contenedores
└── Dockerfile                        # Imagen PHP-FPM 8.3
```

---

## 5. Uso de IA

La aplicación integra **Google Gemini** (paquete `google-gemini-php/laravel`) como LLM para la
generación de resumen.

- **Responsable:** `app/Services/LlmService.php`.
- **Método:** `generarResumen(string $titulo, string $descripcion): string`.
- **Funcionamiento:** construye un prompt de sistema ("asistente especializado en gestión de solicitudes")
  y pide un resumen **claro, breve y profesional de máximo 50 palabras**. Se invoca al crear la solicitud
  desde `SolicitudService::createRequest()` y el resultado se guarda en la columna `llm_summary`.
- **Modelo:** configurable vía `GEMINI_MODEL` (por defecto se usa el valor del `.env`, p. ej.
  `gemini-3.8-flash`).
- **Autenticación:** `GEMINI_API_KEY` (obtenible en Google AI Studio).
- **Manejo de errores:** si Gemini falla o expira (`request_timeout` = 30s), el error se registra en el
  log (`Log::error`) y la solicitud se guarda con `llm_summary = null`. **La IA no bloquea la creación
  de solicitudes.**


---

## 6. Información relevante de la base de datos

- **Motor:** MySQL 8.0 (en Docker) / configurable por `DB_CONNECTION`. Charset `utf8mb4`,
  collation `utf8mb4_unicode_ci`.
- **Base de datos:** `puesto_dev_modd_hunter`.

### Tabla `requests` — migración `2026_11_04_195209_create_solicituds_table`

| Columna       | Tipo                     | Restricciones            | Descripción                              |
| ------------- | ------------------------ | ------------------------ | ---------------------------------------- |
| `id`          | BIGINT UNSIGNED          | PK, AUTO_INCREMENT       | Identificador.                           |
| `title`       | VARCHAR(255)             | NULL                     | Título de la solicitud.                  |
| `description` | TEXT                     | NULL                     | Descripción de la solicitud.             |
| `status`      | VARCHAR                  | DEFAULT `created`        | Estado actual (ver `EstadosSolicitud`).  |
| `llm_summary` | TEXT                     | NULL                     | Resumen generado por Gemini.             |
| `created_at`  | TIMESTAMP                | NULL                     | Fecha de creación.                       |
| `updated_at`  | TIMESTAMP                | NULL                     | Fecha de última actualización.           |
| `deleted_at`  | TIMESTAMP                | NULL                     | Soft delete (el registro no se elimina). |

El estado en el modelo se castea al enum `App\Enums\EstadosSolicitud` (valores: `created`, `in_review`,
`in_progress`, `paused`, `done`). Las columnas `created_at`, `updated_at` y `deleted_at` están ocultas
en las respuestas JSON.

### Seeders

- `DatabaseSeeder`: crea un usuario de prueba (`test@example.com`).
- `SolicitudSeeder`: sin datos por defecto (listo para poblar `requests`).

> **Nota:** el método `down()` de la migración elimina la tabla `solicituds`, mientras que `up()`
> crea `requests`. Conviene alinear ese nombre para que `php artisan migrate:rollback` funcione
> correctamente.

---

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
