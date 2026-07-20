# MagratheaApiControl — API Controller Base

**File:** `src/MagratheaApiControl.php`
**Namespace:** `Magrathea2`

Base class for all API controllers. Provides HTTP request parsing, JWT authentication helpers, CRUD stubs, and caching utilities.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$model` | `?string` | Associated model class name |
| `$service` | `?object` | Optional service/helper object |
| `$userInfo` | `?object` | Decoded JWT payload after token validation |
| `$jwtEncodeType` | `string` | JWT algorithm (default: `"HS256"`) |

---

## Defining a Controller

```php
<?php
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaApiException;
use App\Controls\ProductControl;

class ProductApiControl extends MagratheaApiControl {

    public function List(): array {
        return ProductControl::GetAll();
    }

    public function Read(array $params = []): object|array {
        $id = $params["id"] ?? null;
        $product = ProductControl::GetRowWhere(["id" => $id]);
        if (!$product) throw new MagratheaApiException("Product not found", 404);
        return $product->ToJson();
    }

    public function Create(array $data = []): object {
        $post = $this->GetPost();
        // validate, create, return
    }

    public function Update(array $params): object {
        $put = $this->GetPut();
        // find, update, return
    }

    public function Delete(array $params = []): bool {
        $id = $params["id"] ?? null;
        $product = ProductControl::GetRowWhere(["id" => $id]);
        if (!$product) throw new MagratheaApiException("Not found", 404);
        return $product->Delete();
    }
}
```

---

## HTTP Request Methods

### `GetAllHeaders(): array<string, string>`
Returns all HTTP request headers as an associative array.

```php
$headers = $this->GetAllHeaders();
echo $headers["Content-Type"];
```

### `GetPost(): ?array`
Returns the decoded POST body. Handles both `application/json` and `application/x-www-form-urlencoded`.

```php
$data = $this->GetPost();
$name  = $data["name"]  ?? "";
$email = $data["email"] ?? "";
```

### `GetPut(): ?array`
Returns the decoded PUT request body (reads from `php://input`).

```php
$data = $this->GetPut();
$newPrice = $data["price"] ?? null;
```

### `GetPhpInput(): mixed`
Returns the raw body of the current request (any method).

```php
$raw = $this->GetPhpInput();
```

---

## JWT Authentication Methods

### `GetSecret(): string`
Returns the JWT secret from the application config. Override this to use a custom secret.

```php
// In your AuthControl:
public function GetSecret(): string {
    return Config::Instance()->Get("jwt_secret");
}
```

### `jwtEncode(mixed $payload): string`
Encodes a payload into a JWT token string.

```php
$token = $this->jwtEncode([
    "user_id" => 42,
    "role"    => "admin",
    "exp"     => time() + 3600,
]);
```

### `jwtDecode(string $token): object`
Decodes a JWT token and returns the payload as an object.

```php
$payload = $this->jwtDecode($token);
echo $payload->user_id;
```

### `GetAuthorizationToken(): string`
Extracts the Bearer token from the `Authorization` header. Throws `MagratheaApiException` if missing.

```php
$token = $this->GetAuthorizationToken();
```

### `GetTokenInfo(string|false $token = false): object|false`
Decodes the current request's Bearer token. Returns the payload object or `false` on failure.

```php
$info = $this->GetTokenInfo();
if (!$info) {
    throw new MagratheaApiException("Invalid token", 401);
}
```

### `GetUserInfo(): ?object`
Returns the decoded user payload set during authorization.

```php
$user = $this->GetUserInfo();
echo $user->user_id;
```

### `GetUserId(): int|null`
Returns the `user_id` from the decoded token, or `null` if not set.

```php
$userId = $this->GetUserId();
```

---

## CRUD Stub Methods

These are meant to be **overridden** in subclasses. By default they throw `Exception`.

| Method | HTTP Method | Route |
|--------|-------------|-------|
| `List(): array` | GET | `/resource` |
| `Read($params): object\|array` | GET | `/resource/{id}` |
| `Create($data): object` | POST | `/resource` |
| `Update(array $params): object` | PUT | `/resource/{id}` |
| `Delete($params): bool` | DELETE | `/resource/{id}` |

---

## Caching Methods

### `Cache(string $name, string|null $data = null): void`
Store a response in the file cache under the given name.

```php
public function List(): array {
    $this->Cache("products_list");
    $result = ProductControl::GetAll();
    $this->Cache("products_list", json_encode($result));
    return $result;
}
```

### `CacheClear(string $name, string|null $data = null): void`
Clear a specific cache entry.

```php
public function Create(array $data = []): object {
    // ... create product ...
    $this->CacheClear("products_list");
    return $product->ToJson();
}
```

### `CacheClearPattern(string $pattern): void`
Clear all cache entries matching a pattern.

```php
$this->CacheClearPattern("products_*");
```

---

## Raw Output

### `Raw(string $content): void`
Output raw (non-JSON) content and exit. Useful for file downloads, CSV exports, etc.

```php
public function ExportCsv(): void {
    header("Content-Type: text/csv");
    $this->Raw("id,name,price\n1,Widget,9.99\n");
}
```

---

## Complete Example: User Authentication API

```php
<?php
namespace App\Api;

use Magrathea2\MagratheaApiControl;
use Magrathea2\Exceptions\MagratheaApiException;
use App\Controls\UserControl;

class AuthApiControl extends MagratheaApiControl {

    // Called for every protected route as base authorization
    public function ValidateToken(): bool {
        $token = $this->GetAuthorizationToken();
        $info  = $this->GetTokenInfo($token);

        if (!$info || !isset($info->user_id)) {
            throw new MagratheaApiException("Unauthorized", 0, null, true);
        }

        // Store for later use in other methods
        $this->userInfo = $info;
        return true;
    }

    // POST /auth/login
    public function Login(): array {
        $post  = $this->GetPost();
        $email = $post["email"] ?? "";
        $pass  = $post["password"] ?? "";

        $user = UserControl::GetRowWhere(["email" => $email]);

        if (!$user || !password_verify($pass, $user->password_hash)) {
            throw new MagratheaApiException("Invalid credentials", 401);
        }

        $token = $this->jwtEncode([
            "user_id" => $user->id,
            "email"   => $user->email,
            "exp"     => time() + 86400, // 24 hours
        ]);

        return ["token" => $token, "user" => $user->ToJson()];
    }

    // GET /auth/me
    public function Me(): array {
        $userId = $this->GetUserId();
        $user   = UserControl::GetRowWhere(["id" => $userId]);
        return $user->ToJson();
    }
}
```

```php
// In index.php:
$api->Add("POST", "/auth/login", new AuthApiControl(), "Login");
$api->Add("GET",  "/auth/me",    new AuthApiControl(), "Me", true);
```

---

## Notes

- `GetPost()` and `GetPut()` automatically handle both JSON and form-encoded bodies.
- The `$userInfo` property is populated by your authorization method and accessible in subsequent calls.
- Override `GetSecret()` if you want to use a config-driven or environment-variable JWT secret.
