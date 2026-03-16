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

MagratheaPHP::LoadVendor();
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

### `BaseAuthorization(MagratheaApiControl $authClass, ?string $function): MagratheaApi`
Set a default authorization check applied to all protected routes.

```php
$api->BaseAuthorization(new AuthControl(), "ValidateToken");
```

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

### `Fallback(callable $fn): MagratheaApi`
Register a fallback handler for routes that don't match anything.

```php
$api->Fallback(function() {
    return ["error" => "Route not found"];
});
```

### `HealthCheck(): void`
Registers a `GET /health` endpoint that returns `{"status": "ok"}`.

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
Output a JSON response with an HTTP status code.

```php
$api->Json(["error" => "Not found"], 404);
```

### `ReturnSuccess(mixed $data): mixed`
Return a standardized success response:

```json
{"success": true, "data": ...}
```

### `ReturnFail(mixed $data): mixed`
Return a standardized failure response:

```json
{"success": false, "data": ...}
```

### `ReturnError(int $code = 500, string $message = "", mixed $data = null, int $status = 200): mixed`
Return a standardized error response.

```php
$api->ReturnError(401, "Unauthorized");
```

### `Return404(): mixed`
Return a 404 response.

### `ReturnApiException(MagratheaApiException $exception): mixed`
Return an error response derived from an exception.

### `Cache(array $data): void`
Store and immediately return a cached response.

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

MagratheaPHP::LoadVendor();
MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->AddCodeFolder("models", "controls", "api")
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
