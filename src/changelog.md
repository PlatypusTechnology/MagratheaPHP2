### 2.3.2
2026-09
	- **new:** `MagratheaApi::ReturnApiException()` gained a third exception-code→HTTP-status bucket for 5-digit codes (`10000`-`59999`), mapped as `intval($exCode / 100)` — giving `XXXYY` where `XXX` is the 3-digit HTTP status and `YY` is a 00-99 sub-code, i.e. 100 sub-codes per status instead of the existing 4-digit scheme's 10 (`4030`-`4039` etc). Purely additive: the new range sits above the existing `1000`-`9999` bucket with no overlap, so no 3-digit or 4-digit code changes meaning. Added because a consumer (guia.lol's API) exhausted its 403 sub-code bucket (4031-4038 in use, only 4039 free) and a 5-digit code previously fell through to the `else` branch, silently returning a misleading HTTP 500 instead of the intended status
	- **note:** this logic is not duplicated anywhere else in the package — `ReturnApiException()` is the only place exception codes are mapped to HTTP status codes

### 2.3.1
2026-08
	- **fix:** two admin dispatch paths could call a feature's action method while skipping `AdminFeature::HasPermission()` entirely — `Start::CheckFeature()` (the `?magrathea_feature=X&magrathea_feature_action=Y` route) called `$feature->$action()` directly with no permission check, and `views/index.php`'s subpage dispatch called `$f->$subpage()` directly for any explicit `magrathea_feature_subpage` value, also unchecked. Only the default subpage path (`AdminFeature::GetPage()`) enforced `HasPermission()`. Both bypasses are now closed: `CheckFeature()` and the explicit-subpage branch of `views/index.php` call `HasPermission($action)`/`HasPermission($subpage)` before dispatching, rejecting via the same `AdminManager::PermissionDenied()` used everywhere else. The default (`GetPage()`) path is unchanged — it already enforced this itself
	- **note:** this is a behavior change only for a feature that has overridden `HasPermission()` with logic that assumes it is only ever called from the default subpage path. `AdminFeature::HasPermission()`'s base implementation returns `true` unconditionally, and the only in-repo override (`AdminFeatureUserLog`) also always returns `true`, so this is a no-op for any feature that hasn't written a custom override. As with the 2.3.0 CSRF enforcement, this ships immediately with no opt-out or staged rollout

### 2.3.0
2026-08
	- **new:** `AdminCsrf` class (`src/Admin/AdminCsrf.php`) — CSRF token management for the admin panel: `GetToken()` (get-or-create), `Regenerate()`, `Validate()`. The token is seeded on every admin request (`Start::Initialize()`) and rotated on login (`AdminUsers::Login()`)
	- **new:** the admin panel now enforces CSRF protection on every authenticated POST request, from this release, with no opt-out. `Start::Load()` validates a `magrathea_csrf_token` POST field (falling back to an `X-Magrathea-CSRF-Token` header) before dispatching to `CheckApi()`/`CheckFeature()`/`views/index.php`; a missing or invalid token is rejected via `AdminManager::PermissionDenied()` and logged to the `csrf` log file. The token is emitted automatically by the shared `views/elements/form.php` template (used by `AdminForm::Print()`) and attached automatically to every POST made through the shared `ajax()` function (`callAction()`/`callApi()`/`callFeature()`) — no per-call-site changes needed for admin code already using those helpers. GET requests are deliberately exempt
	- **fix:** `views/actions/object-remove.php` (object deletion, triggered by `deleteObject()` in `object-creation.js`) moved from a GET-based delete to POST — a GET-based mutation can never be safely protected by an embedded token, since URLs leak via browser history, server/proxy logs, and `Referer` headers
	- **note:** this changes behavior for any consumer with raw admin forms or custom AJAX calls outside the two centralized paths above (`AdminForm`/`ajax()`) — those specific actions will start failing on upgrade with "Permission denied!" until updated to send the token. There is no `admin_csrf_mode` config and no staged rollout; enforcement is immediate
	- **new:** `MagratheaModel::LoadObjectFromTableRow()` gained opt-in typed hydration — set `protected $strictTypes = true;` on a model (or its Base class) and DB row values are cast to their declared `$dbValues` PHP type (`int`/`boolean`/`float`) instead of staying the raw string every DB driver returns. `null` is always left as `null`; `string`/`text`/`uuid`/`date`/`datetime` fields are untouched. A value that can't be cast (e.g. schema drift — a non-numeric string in an `int` column) throws `MagratheaModelException` naming the field and value. Opt-in via `$strictTypes`, off by default — zero behavior change for existing models
	- **change:** `MagratheaApiControl::GetCookieName()` converted from a method to a plain property, `protected ?string $cookieName = null;`, matching the shape of its siblings `$jwtEncodeType`/`$forceSecureCookie` — it never had any logic beyond returning a static value. Projects opting into cookie auth now override `$cookieName` instead of overriding the method; no project on record overrides the old method, so this ships as a straight rename with no compatibility shim

### 2.2.4
2026-08
	- **new:** `MagratheaApiControl::GetTokenInfo()` gained a cookie-based fallback, tried after `Bearer`/`Basic`, so a session can be recognized across subdomains via a cookie instead of a header. Opt-in only: the new `GetCookieName()` hook returns `null` by default (no behavior change for any existing project), and must be overridden in a project's own `ApiControl` subclass to enable it — same convention as `GetSecret()`
	- **new:** `MagratheaApiControl::SetAuthCookie()` / `ClearAuthCookie()` — issues/clears the session cookie, deriving its expiry from the JWT's own `exp` claim. `SetAuthCookie()` accepts a `$domain` (e.g. `.example.com` to share a session across subdomains), and defaults to `HttpOnly` + `SameSite=Lax`. Because `HttpOnly` cookies can't be cleared client-side, logout now needs a real server round-trip through `ClearAuthCookie()`
	- **new:** `MagratheaPHP::IsDev()` — read-accessor for whether the project was started with `->Dev()`. Used by `SetAuthCookie()`/`ClearAuthCookie()` to only mark the cookie `Secure` outside of dev, since a `Secure` cookie is never sent by the browser over plain `http://localhost`

### 2.2.3
2026-08
	- **fix:** `AdminManager::PrintLogo()` rendered SVG logos with literal `<?=$logoSize?>` text in the `width`/`height` attributes instead of the actual size — `file_get_contents()` reads the default `views/logo.svg` template as raw bytes without evaluating its PHP placeholder. Now does a plain string substitution on the SVG content instead of executing it, fixing the default template while staying inert for custom SVG logos

### 2.2.2
2026-07
	- **new:** `"date"` field type for models — SQL `date` columns are now detected as `"date"` (instead of collapsing into `"datetime"`) when generating objects; on `Insert()`/`Update()` the value is normalized to `Y-m-d` before binding, accepting `"YYYY-MM-DD"`, `"YYYY-MM-DD HH:MM:SS"` and ISO-8601 (`"YYYY-MM-DDTHH:MM:SS.sssZ"`), and throwing a `MagratheaModelException` if the value is not a parseable date. Note: the ISO-8601 date part is taken as written — no timezone conversion is applied
	- **backward compatibility:** existing generated Base files and `magrathea_objects.conf` entries that say `"datetime"` for SQL `date` columns keep working exactly as before; the new type only applies once a project regenerates its objects. Admin auto-CRUD renders `"date"` fields as `<input type="date">`
	- **fix:** `MagratheaApi::Json()` sent a malformed `Status: Unauthorized` header for 401 responses (missing the leading `401`), which Caddy/PHP-FPM couldn't parse — causing a bare 502 instead of the real 401 response
	- **fix:** `MagratheaApi::Json()` threw an undefined-array-key warning for any HTTP status code outside `200/400/401/422/500` (e.g. `403`, `404`, `409`) — under `display_errors=On` this leaked an HTML warning into the JSON response body and broke the subsequent `header()` call. Replaced the partial status map with a full reason-phrase table plus an `'Unknown Status'` fallback for unlisted codes

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
