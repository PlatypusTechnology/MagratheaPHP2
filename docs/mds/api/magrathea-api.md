# MagratheaApi — RESTful API Framework

**File:** `src/MagratheaApi.php`
**Namespace:** `Magrathea2`

The central class for building RESTful APIs. Handles routing, CORS, request parsing, response formatting, JWT authorization, and caching. The recommended entry point for any API-first application.

---

## Quick Start

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;

MagratheaPHP::Instance()->AppPath(__DIR__)->Prod()->Load()->Connect();

$api = new MagratheaApi();
$api->AllowAll();

$api->Add("GET", "/ping", null, function() {
    return ["status" => "ok"];
});

$api->Run();
```

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$control` | `string` | Default control class |
| `$action` | `string` | Default action |
| `$params` | `array` | Request parameters |
| `$returnRaw` | `bool` | Whether to skip JSON encoding |
| `$apiAddress` | `?string` | Base API path prefix |
| `$authClass` | `?MagratheaApiAuth` | Authorization class |
| `$baseAuth` | `?string` | Default auth method name |
| `$endpoints` | `array` | All registered routes |
| `$fallback` | `callable\|null` | Fallback for unmatched routes |

---

## Configuration Methods

### `SetAddress(string $addr): MagratheaApi`
Set a base path prefix for all routes (e.g., `/api/v1`).

```php
$api->SetAddress("/api/v1");
// Routes like "/users" become "/api/v1/users"
```

### `GetAddress(): string|null`
Returns the current base address.

### `AllowAll(): MagratheaApi`
Set `Access-Control-Allow-Origin: *` (CORS open to all origins).

```php
$api->AllowAll();
```

### `Allow(array $allowedOrigins): MagratheaApi`
Restrict CORS to specific origins.

```php
$api->Allow(["https://myapp.com", "https://admin.myapp.com"]);
```

### `DisableCache(): MagratheaApi`
Send `Cache-Control: no-cache` headers.

### `AddAcceptHeaders(string|array $accept): void`
Append allowed request headers.

```php
$api->AddAcceptHeaders(["X-Api-Key", "X-Custom-Header"]);
```

### `AcceptHeaders(?array $headers): void`
Set the full list of accepted request headers.

### `SetRaw(): MagratheaApi`
Disable JSON encoding of responses — return raw output.

Raw mode (`SetRaw()`, `Run(true)`) also bypasses the response cache: `ExecuteUrl()` calls
`MagratheaCache::Instance()->Bypass()`, so a cached action always runs and returns fresh data, and
nothing is saved. Before 2.4.0, a cache hit or save in raw mode printed the JSON and ended the script.

### `BaseAuthorization(MagratheaApiAuth $authClass, ?string $function): MagratheaApi`
Set a default authorization check applied to all protected routes. `$authClass` must extend
`MagratheaApiAuth` (see the Authentication page); a plain `MagratheaApiControl` throws a
`TypeError`. Before 2.4.0 the parameter was typed `MagratheaApiControl`, but a plain control failed
anyway when it was stored.

```php
$api->BaseAuthorization(new AuthControl(), "ValidateToken");
```

When a protected route's auth method returns `false`, the request stops there: the response is a
`401` error (`code` 401, `"Authorization Failed! (<method> = false)"`, with the auth class's
`authenticationErrorData` as `error`) and the action is never called. This holds in raw mode too
(`Run(true)`, `SetRaw()`, `ExecuteUrl()`), where the error array is returned instead of printed.
Before 2.4.0, raw mode ran the action anyway after building the error.

---

## Registering Endpoints

### `Add(string $method, string $url, ?MagratheaApiControl $control, string|callable $function, string|bool $auth = false, ?string $description = null): MagratheaApi`

Register a single route.

| Parameter | Description |
|-----------|-------------|
| `$method` | HTTP verb: `"GET"`, `"POST"`, `"PUT"`, `"DELETE"`, `"PATCH"` |
| `$url` | Route pattern, supports `{param}` placeholders |
| `$control` | Controller instance (`null` for closure-only routes) |
| `$function` | Method name string or a `callable` (closure) |
| `$auth` | `false` = no auth, `true` = use base auth, `string` = named auth method |
| `$description` | Optional route description (shown in API explorer) |

```php
// Closure route
$api->Add("GET", "/hello", null, function() {
    return ["message" => "Hello!"];
});

// Controller method
$api->Add("GET", "/users", new UserApiControl(), "List");
$api->Add("GET", "/users/{id}", new UserApiControl(), "Read");
$api->Add("POST", "/users", new UserApiControl(), "Create", true); // auth required

// Route with description
$api->Add("DELETE", "/users/{id}", new UserApiControl(), "Delete", true,
    "Delete a user by ID (requires admin token)");
```

### `Crud(string|array $url, MagratheaApiControl $control, string|bool $auth = false): MagratheaApi`

Registers a full CRUD suite for a resource in one call:

| Method | Route | Controller Method |
|--------|-------|-------------------|
| GET | `/resource` | `List()` |
| GET | `/resource/{id}` | `Read($params)` |
| POST | `/resource` | `Create($data)` |
| PUT | `/resource/{id}` | `Update($params)` |
| DELETE | `/resource/{id}` | `Delete($params)` |

```php
$api->Crud("/products", new ProductApiControl());

// With auth on all CRUD methods:
$api->Crud("/products", new ProductApiControl(), true);
```

`Crud()` always registers `Update` under `PUT`, never `PATCH` — if you also want a `PATCH`
route for the same resource/handler, register it yourself with `Add()`:

```php
$api->Crud("/products", new ProductApiControl());
$api->Add("PATCH", "/products/{id}", new ProductApiControl(), "Update", true);
```

Both `PUT` and `PATCH` bodies are read the same way in a controller — `GetPut()` (or its
alias `GetPatch()`) returns the decoded body regardless of which of the two verbs was used.

### `Fallback(callable $fn): MagratheaApi`
Register a fallback handler for routes that don't match anything.

```php
$api->Fallback(function() {
    return ["error" => "Route not found"];
});
```

### `HealthCheck(bool $checkDatabase = false): void`
Registers a `GET /health-check` endpoint that always returns `health` and `time`.

When `$checkDatabase` is `true`, it also checks DB connectivity and adds `database` with `"ok"` or `"fail"`.

```php
$api->HealthCheck();
// {"health":"ok","time":"2026-07-20 10:00:00"}

$api->HealthCheck(true);
// {"health":"ok","time":"2026-07-20 10:00:00","database":"ok"}
```

---

## Running the API

### `Run(bool $returnRaw = false): mixed`
Matches the incoming request to a route, calls the controller/closure, and outputs the JSON response.

```php
$api->Run();
```

### `ExecuteUrl(string $fullUrl, string $method = "GET"): mixed`
Manually execute a URL against this API (useful for internal subrequests or tests).

```php
$result = $api->ExecuteUrl("/products/1", "GET");
```

---

## Response Helpers

These are typically called from within controllers, but can also be called directly.

### `Json(array|object $response, int $code = 200): mixed`
Output a JSON response with an HTTP status code. In DEV and DEBUG, an associative-array response also
gets `_debug` (see [DEV and DEBUG: debug items go to `_debug`](#dev-and-debug-debug-items-go-to-debug)).

```php
$api->Json(["error" => "Not found"], 404);
```

### `ReturnSuccess(mixed $data): mixed`
Return a standardized success response:

```json
{"success": true, "data": ...}
```

If `$data` is a `MagratheaPagination` instance, a paginated envelope is returned instead:

```json
{"success": true, "data": [...], "page": 0, "count": 20, "has_more": true, "total": 143}
```

`total` is only included when it was computed (see `MagratheaModelControl::GetPagination()`). Just return a `MagratheaPagination` from a controller action — no other code changes needed:

```php
public function List(array $params = []): MagratheaPagination {
    $query = Query::Select()->Obj(new Article())->Where(["published" => 1])->Order("created_at DESC");
    return ArticleControl::GetPagination($query, page: (int)($params["page"] ?? 0), limit: 20);
}
```

### `ReturnFail(mixed $data): mixed`
Return a standardized failure response:

```json
{"success": false, "data": ...}
```

An exception (any `\Throwable`) is sent as `{"message": ..., "code": ...}` (`code` only when it isn't 0),
plus `"data"` for a `MagratheaApiException` with data, and the HTTP status comes from the exception
(see [Error responses and HTTP status](#error-responses-and-http-status)): 500 for code 0. Anything
else is sent as `data` as is, with HTTP **200**.

### `ReturnError(int $code = 500, string $message = "", mixed $data = null, ?int $status = null): mixed`
Return a standardized error response. With no `$status`, the HTTP status is derived from `$data` when
it's an exception (as in [Error responses and HTTP status](#error-responses-and-http-status)), otherwise
from `$code`; an explicit `$status` is sent as given.

```php
$api->ReturnError(404, "Not here");          // HTTP 404
$api->ReturnError(4031, "Plan expired");     // HTTP 403
$api->ReturnError(1062, "Duplicate entry");  // HTTP 500 (doesn't map to 400-599)
$api->ReturnError(4031, "Soft fail", null, 200); // HTTP 200, explicit
```

### `Return404(): mixed`
Return a 404 response (HTTP 404).

### `ReturnInternalError(\Error $error): mixed` (protected)
Sends a PHP `\Error` (`TypeError`, `Error`, ...) thrown by an action or an auth method as HTTP 500, and
logs it with `Logger::LogError()` (class, message, file and line); if the Logger can't write, it goes
to PHP's `error_log()`. The client gets the details only in DEV:

```json
// DEV
{"success": false, "data": {"message": "...must be of type int, string given...", "error": "TypeError", "file": "/var/www/app/Api/X.php:40"}}
// any other mode
{"success": false, "data": {"message": "Internal Server Error"}}
```

Before 2.4.0 these weren't caught: PHP's fatal error page was sent instead of JSON. `AdminApi` does the
same (it runs in LOG mode, so its clients always get the generic message).

### `ReturnApiException(MagratheaApiException $exception): mixed`
Return an error response derived from an exception, with the HTTP status from the exception.

When the debugger is `DEV` or `ANALYSIS`, `ReturnError()` and `ReturnApiException()` add a
`stacktrace` to `data`. Each frame has `file`, `line`, `function`, `class` and `type`, but no `args`
(since 2.4.0), so config arrays, passwords and keys passed down the call chain never reach the
response. In other debug modes there's no `stacktrace` at all.

### `Cache(array $data): void`
Store and immediately return a cached response.

### Error responses and HTTP status

Every error response has a 4xx/5xx HTTP status (since 2.4.0; before, everything except
`ReturnApiException()` and the 401 of a failed authorization was sent as HTTP 200). The body is
unchanged: `{"success": false, "data": ...}`.

The status comes from the error code:

| Code | HTTP status | Example |
|------|-------------|---------|
| 3 digits | the code | `404` → 404 |
| 4 digits | code / 10 | `4031` → 403 (sub-codes 0-9) |
| 5 digits, 10000-59999 | code / 100 | `40312` → 403 (sub-codes 00-99) |
| anything else, or a result outside 400-599 | 500 | `0`, `1062` (`106`), `2001` (`200`), `7000` (`700`), `"23000"` |

For a `MagratheaApiException`, a status set with `SetStatus()` wins if it's 400-599; otherwise the
code decides. Whether it's a "killer" error (`$kill`) doesn't change the status.

A database error (`MagratheaDBException`, including `MagratheaDBConnectionLostException`, or a raw
`mysqli_sql_exception`) is always **500** (since 2.4.0): its code is MySQL's errno, not a status, and
some errnos would map to a 4xx (MySQL's `4031` → 403, MariaDB's `4025` → 402).

What `ExecuteUrl()` sends:

| Case | Response | HTTP status |
|------|----------|-------------|
| no route | `ReturnError(404, ...)` | 404 |
| auth method returned `false` | `ReturnError(401, ...)` | 401 |
| action method doesn't exist | `ReturnError(500, ...)` | 500 |
| `MagratheaApiException` | `ReturnApiException()` | `SetStatus()` or from the code |
| other exception, code 0 | `ReturnFail()`: `{message}` | 500 |
| other exception, code ≠ 0 | `ReturnError(code, message, exception)` | from the code (500 for a DB error) |
| PHP `\Error` (`TypeError`, ...) in the action or the auth method | `ReturnInternalError()` | 500 |

In raw mode (`Run(true)`, `SetRaw()`) nothing is sent: the response array is returned, without the status.

Clients that don't reject on 4xx/5xx (`fetch()`) see no difference. axios, jQuery `$.ajax`, Angular
`HttpClient` and similar send these responses to their error handler, where the body is in
`error.response.data` (axios) / `jqXHR.responseJSON` (jQuery).

---

## Endpoint Introspection

### `GetEndpoints(): array`
Returns all registered endpoints grouped by URL and method.

### `GetEndpointsDetail(): array`
Returns detailed endpoint info including descriptions.

```php
$endpoints = $api->GetEndpoints();
// Used by the Admin API Explorer feature
```

---

## Debug Mode

### `Debug(): MagratheaApi`
Enable endpoint debugging — outputs route resolution info.

```php
$api->Debug()->Run();
```

### DEV and DEBUG: debug items go to `_debug`

In DEV, the `Debugger` prints every debug item as `<pre>` where it's added, including every
`MagratheaException` when it's created, even ones that are caught. In DEBUG (`->Debug()`) it prints them
all at the end of the request. In an API response that HTML would break the JSON, so while `ExecuteUrl()`
runs (and so `Run()`), DEV and DEBUG collect the items instead, and the response gets them as `_debug`
(since 2.4.0). In DEBUG, `_debug` starts with the items stored before the call, and nothing is printed
after a JSON response:

```json
{
  "success": true,
  "data": {"id": 7},
  "_debug": [
    {"type": "Magrathea2\\Exceptions\\MagratheaException", "message": "caught one", "code": 42, "file": "/var/www/app/Api/X.php:40", "data": {"id": 7}},
    {"type": "Magrathea2\\Exceptions\\MagratheaDBException", "message": "Duplicate entry...", "code": 0, "file": "...", "query": "INSERT ...", "values": ["..."]},
    "a string passed to Debugger::Add()",
    "INFO: from Debugger::Info()"
  ]
}
```

- Only in DEV and DEBUG, and only when there's something to send: other modes never have `_debug`.
- Only on associative-array responses: a list or a scalar passed to `Json()` is sent unchanged.
- Exceptions are `{type, message, code, file, data?, query?, values?}` (`query`/`values` for
  `MagratheaDBException`); other items go in as they were added. No stack trace.
- A cache save (`Cache()` in the action) sends `_debug` in that response but never writes it to the
  cache file; a cache hit sends the file as is, without `_debug`.
- Raw mode (`Run(true)`, `SetRaw()`) returns the data without `_debug`.
- `_debug` can hold SQL and its values (`->Debug()` logs every query): don't run a public server in
  DEV or DEBUG.

---

## URL Parameter Parsing

Route parameters defined with `{param}` are passed to the controller method:

```php
$api->Add("GET", "/users/{id}/orders/{orderId}", new OrderControl(), "GetByUser");

// In OrderControl:
public function GetByUser(array $params): array {
    $userId  = $params["id"];
    $orderId = $params["orderId"];
    // ...
}
```

---

## Full Example: Products API

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Magrathea2\MagratheaPHP;
use Magrathea2\MagratheaApi;
use App\Api\ProductApiControl;
use App\Api\AuthControl;

MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->AddRootCodeFolder("models", "controls", "api")
    ->Prod()
    ->Load()
    ->Connect();

$api = new MagratheaApi();

$api->Allow(["https://myapp.com"])
    ->SetAddress("/api/v1")
    ->BaseAuthorization(new AuthControl(), "ValidateToken")
    ->DisableCache();

// Public endpoints
$api->Add("GET",  "/products",     new ProductApiControl(), "List");
$api->Add("GET",  "/products/{id}", new ProductApiControl(), "Read");

// Protected endpoints
$api->Add("POST",   "/products",      new ProductApiControl(), "Create", true);
$api->Add("PUT",    "/products/{id}", new ProductApiControl(), "Update", true);
$api->Add("DELETE", "/products/{id}", new ProductApiControl(), "Delete", true);

$api->HealthCheck();

$api->Run();
```

---

## Notes

- `Run()` outputs directly to `php://output` and exits. It should be the last call.
- CORS preflight (`OPTIONS` requests) are handled automatically.
- The API always returns `Content-Type: application/json` unless `SetRaw()` is used.
- Use `SetAddress()` consistently if your API lives under a sub-path (e.g., `/api/v2`).
