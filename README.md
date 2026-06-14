# Ticketera

Sistema de tickets moderno construido con Laravel 13 + Vue 3 + Inertia + MariaDB.

## Requisitos

Solo necesitás **Docker** y **Docker Compose**. No hace falta PHP, Composer ni Node en el host.

## Inicio rápido

```bash
cd ticketera
./bin/start.sh
./bin/artisan migrate --seed
```

Abrí http://127.0.0.1:8000

**Browser integrado de Cursor:** `Ctrl+Shift+P` → `Simple Browser: Show` → `http://127.0.0.1:8000`

## Servicios Docker

| Servicio  | Contenedor        | Puerto | Descripción                          |
| --------- | ----------------- | ------ | ------------------------------------ |
| `mariadb` | ticketera-mariadb | 3307   | Base de datos                        |
| `redis`   | ticketera-redis   | 6379   | Cache, sesiones y colas              |
| `app`     | ticketera-app     | —      | PHP-FPM (procesa requests vía Nginx) |
| `nginx`   | ticketera-nginx   | 8000   | Servidor web                         |
| `queue`   | ticketera-queue   | —      | Worker de colas (emails, etc.)       |
| `vite`    | ticketera-vite    | 5173   | Frontend hot reload                  |

## Comandos útiles

```bash
./bin/start.sh              # Levantar todo (incluye composer install y .env)
docker compose down         # Detener
./bin/logs.sh               # Ver logs en vivo
./bin/artisan migrate       # Migraciones
./bin/artisan db:seed       # Datos demo
./bin/artisan test          # Tests PHPUnit
./bin/check.sh              # Suite completa de calidad (CI local)
```

### Suite de calidad (`./bin/check.sh`)

Ejecuta localmente los mismos checks que GitHub Actions:

| Herramienta            | Qué verifica                         |
| ---------------------- | ------------------------------------ |
| **Composer validate**  | `composer.json` bien formado         |
| **Composer audit**     | Vulnerabilidades en dependencias PHP |
| **Laravel Pint**       | Estilo de código PHP                 |
| **PHPStan + Larastan** | Análisis estático (nivel 8)          |
| **Rector**             | Refactors pendientes (dry-run)       |
| **PHPUnit**            | 50+ tests de feature/unit            |
| **npm audit**          | Vulnerabilidades en dependencias JS  |
| **ESLint**             | Calidad Vue/JS                       |
| **Prettier**           | Formato frontend                     |
| **Vite build**         | Compilación de assets                |

También podés correr solo la parte PHP con `composer check` dentro del contenedor.

Los correos de notificación se encolan en **Redis** y los procesa el servicio `queue` (driver `log` en desarrollo → `storage/logs/laravel.log`).

## Rendimiento

Optimizaciones para uso intensivo incluidas por defecto en Docker:

| Componente            | Qué hace                                                                                |
| --------------------- | --------------------------------------------------------------------------------------- |
| **Redis**             | Cache de configuración, sesiones y colas (menos carga en MariaDB)                       |
| **queue**             | Worker que envía emails y tareas en background                                          |
| **Índices DB**        | Filtros rápidos en la cola de tickets (`status`, `department`, `assigned_to`, `due_at`) |
| **Secuencia atómica** | Números de ticket (`TKT-000001`) sin duplicados bajo concurrencia                       |
| **Cache settings**    | `Setting::current()` cacheado 5 min (se invalida al guardar en panel)                   |
| **Nginx + PHP-FPM**   | Múltiples workers PHP (hasta 20) en lugar del servidor embebido                         |

Ver logs del worker: `docker compose logs -f queue`

Configuración FPM/Nginx en `docker/php/` y `docker/nginx/`.

## Usuarios demo

| Rol     | Email                  | Contraseña |
| ------- | ---------------------- | ---------- |
| Admin   | admin@ticketera.test   | password   |
| Agente  | maria@ticketera.test   | password   |
| Cliente | cliente@ticketera.test | password   |

## Estructura

- `/tickets` — Portal cliente
- `/panel/tickets` — Cola de agentes
- `/panel/departments` — ABM departamentos (admin)
- `/panel/agents` — ABM usuarios: clientes, agentes y admins (admin)
- `/panel/settings` — Configuración del sistema (admin)

## Seguridad

- Registro público **desactivado** por defecto (activable en Configuración).
- Clientes nuevos se crean en **Usuarios** (admin).
- Honeypot invisible en crear ticket + rate limit (10/min).
- Cloudflare Turnstile **opcional** en Configuración → Seguridad.

## Autenticación SSO (LDAP / Keycloak)

Configuración en **Panel → Configuración → Autenticación (SSO)**.

| Método       | Descripción                                                                                           |
| ------------ | ----------------------------------------------------------------------------------------------------- |
| **Local**    | Email y contraseña en la base de datos (por defecto).                                                 |
| **LDAP**     | Autenticación contra Active Directory / OpenLDAP. Requiere extensión `php-ldap` (incluida en Docker). |
| **Keycloak** | OpenID Connect vía Socialite. Botón "Iniciar sesión con Keycloak" en `/login`.                        |

### LDAP

1. Elegí **LDAP / Active Directory** como método.
2. Completá host, puerto, Base DN y atributo de usuario (por defecto `mail`).
3. Opcional: Bind DN + password para búsquedas anónimas deshabilitadas.
4. Activá **TLS** si usás LDAPS (puerto 636).

### Keycloak

1. Creá un cliente OIDC en tu realm (tipo _confidential_, acceso _standard flow_).
2. Redirect URI: `https://tu-dominio/auth/keycloak/callback` (en desarrollo: `http://127.0.0.1:8000/auth/keycloak/callback`).
3. Completá URL base, realm, client ID y secret en Configuración.
4. Opcional: desactivá **login local** para forzar SSO.

### Auto-provisión

Si **Crear usuarios automáticamente** está activo, el primer login SSO crea el usuario con el rol configurado (por defecto _Cliente_). Si está desactivado, solo usuarios ya existentes pueden ingresar.

## Desarrollo en Cursor

`Ctrl+Shift+P` → **Tasks: Run Task** → **Ticketera: Dev (Docker)**
