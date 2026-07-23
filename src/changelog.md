### 2.2.1
2026-07
	- **new:** `MagratheaApi::HealthCheck()` gained a `$checkDatabase` param — when `true`, the `GET /health-check` response also includes a `database` field (`"ok"`/`"fail"`) reflecting DB connectivity
	- **fix:** `MagratheaPHP::AppVersion()` now strips trailing line breaks from the `version` file, and returns `"???"` instead of `false` if the file can't be read
	- **new:** `OpenApiAdmin` admin feature — renders a Swagger UI page for a given OpenAPI/Swagger file URL, add via `AddFeature(new OpenApiAdmin("swagger.yaml"))`
	- **new:** `PATCH` for APIs

### 2.2.0
2026-07
	- **new:** `MagratheaPagination` object — return it from an API controller and `MagratheaApi::ReturnSuccess()` automatically builds a paginated JSON envelope (`{success, data, page, count, has_more, total?}`)
	- **new:** `MagratheaModelControl::GetPagination()` — builds a `MagratheaPagination` from a `Query`; by default fetches `limit + 1` rows to compute `has_more` without an extra `COUNT(*)` query
	- **improvement:** `MagratheaModelControl::RunPagination()` gained a `$withTotal` param (default `true`, backward compatible) to skip the `COUNT(*)` query when the total isn't needed

### 2.1.31
2026-07
	- **fix:** `MagratheaApiControl::GetAuthorizationToken()` no longer throws a PHP warning when the `Authorization` header is missing, which in dev could corrupt the response status/body
	- **fix:** __URGENT FIX__: setting charset to `utf8mb4` to accept emojis in the database (`Guia.LOL` urgent change)

### 2.1.30
2026-07
	- **new:** native `"uuid"` field type for models — auto-generates a UUIDv7 on `Insert()` when the field is declared in `$dbValues` and left unset (`Uuid::V7()` helper added)

### 2.1.27
2026-06
	- **fix:** fix error on ApiExplorer Admin

### 2.1.26
2026-06
	- **fix:** fix bug from 2.1.25 on autoloader only for internal classes
	- **improvement:** cleaning code comments
	- **improvement:** Adding Magrathea Version to bootstrap
	- **improvement:** fixing admin logo

### 2.1.25
2026-04
	- **improvement:** API admin now show status and codes
	- **fix:** returning correct status codes on Magrathea API
	- **new:** Debugger mode: ANALYSIS
	- **new:** App Namespaces for `MagratheaPHP`
	- **fix:** fix autoloader only for internal classes

### 2.1.24
2026-02
	- **improvement:** Authentication failing response improved
	- **fix:** SMTP Mail fix
	- **fix:** Database query can run multiplei queries wonderfully

### 2.1.23
2025-12
	- **improvement:** function `Count(Query $q)` in `MagratheaModelControl` for counting rows in a query.
	- **new:** sample for caddy files
	- **fix:** updated all the enums of the code for a better handling

### 2.1.21
2025-10
	- **fix:** fixing `AppConfigFeatureAdmin`

### 2.1.20
2025-10
	- **improvement:** improving MagratheaApi function and documentation
	- **improvement:** improving debugging for unknown errors

### 2.1.19
2025-10
	- **improvement:** improving functions for Magrathea Admin
	- **improvement:** improving CORS
	- **improvement:** improving MagratheaApi function and documentation

### 2.1.18
2025-09-30
	- **new:** config now getting environment variables when starting with `$=`
	- **improvement:** PHP 8.4: dealing with some deprecations...

### 2.1.17
2025-06-18
	- **fix:** Magrathea API debugger fixing closure functions

### 2.1.16
2025-05-19
	- **fix:** Create new Admin User with md5 password
	- **fix:** Update Admin User with md5 password

### 2.1.13
2025-02-11
	- **fix:** ConfigApp saving

### 2.1.11
2025-01-01
	- **improvement:** admin improvements
	- **improvement:** improving insert query generator

### 2.1.9
2024-12-11
	- **improvement:** `GetAppRoot` function on `MagratheaPHP`

### 2.1.8
2024-12-03
	- **fix:** deleting filed cache fixed

### 2.1.7
2024-12-02
post PNC Update
	- **fix:** deleting cache of settings when updating settings
	- **fix:** changing `MagratheaCache->DeleteFile` with new parameter `$addExtension`, default to `true`
	- **fix:** Calling Admin `Initialize` on load
	- **change:** changelog moved to inside `src` for easier deploy
	- **improvement:** log cache delete 
	- **new:** `LogLastError` function on `Logger`
	- **new:** reloading cache button
	- **new:** function `MinVersion` on `MagratheaPHP`
	- **new:** `CacheClearPattern` function on `MagratheaApiControl`

### 2.1.5
by Paulo Martins
dromedario.etc update
	- fix Admin Logo
	- getting put data on ApiControl
	- fixing update from basic api crud
	- fix Join Object 
	- improved `ToArray` model function
	- `CacheClear` function on `MagratheaApiControl`
	- automatic cache on CRUD requests
	- cache fix
	- delete log file from admin

### 2.1.3
by Paulo Martins
	- delete objects from magrathea_objects.conf
	- API fallback
	- move authentication endpoints to parent ApiAuthentication class
