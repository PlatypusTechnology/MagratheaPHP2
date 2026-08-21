# Authentication — JWT Token Generation

**File:** `src/Authentication.php`
**Namespace:** `Magrathea2`

Provides JWT token generation using the `firebase/php-jwt` library. In practice, JWT operations are also available directly on `MagratheaApiControl` (via `jwtEncode` / `jwtDecode`). This class is a standalone utility for token generation outside the API controller context.

---

## Methods

### `GenerateToken(mixed $payload): array`
Generates a signed JWT token from the given payload. Returns an array with two keys:
- `"source"` — the original payload
- `"token"` — the signed JWT string

```php
use Magrathea2\Authentication;

$auth = new Authentication();
$result = $auth->GenerateToken([
    "user_id" => 42,
    "role"    => "admin",
    "exp"     => time() + 3600, // 1 hour from now
]);

echo $result["token"];
// eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## JWT in the API Layer

For full JWT workflow in API controllers, use the methods on `MagratheaApiControl`:

### Encoding (login endpoint)

```php
class AuthControl extends MagratheaApiControl {

    public function Login(): array {
        $post = $this->GetPost();
        // ... validate credentials ...

        $token = $this->jwtEncode([
            "user_id" => $user->id,
            "email"   => $user->email,
            "exp"     => time() + 86400,
        ]);

        return ["token" => $token];
    }
}
```

### Decoding (protected endpoint)

```php
class AuthControl extends MagratheaApiControl {

    // Used as base authorization for all protected routes
    public function ValidateToken(): bool {
        $token   = $this->GetAuthorizationToken(); // reads Authorization: Bearer <token>
        $payload = $this->GetTokenInfo($token);

        if (!$payload) {
            throw new MagratheaApiException("Unauthorized", 0, null, true);
        }

        $this->userInfo = $payload;
        return true;
    }
}
```

### Using the token payload in a controller

```php
class UserApiControl extends MagratheaApiControl {

    public function Profile(): array {
        $userId = $this->GetUserId();           // reads from decoded token
        $info   = $this->GetUserInfo();         // full decoded payload object

        return UserControl::GetRowWhere(["id" => $userId])->ToJson();
    }
}
```

---

## Cookie-Based Auth (Opt-In)

`GetTokenInfo()` falls back to a cookie when no `Authorization` header is present — `Bearer`, then `Basic`, then a cookie. This lets a session be recognized without a client having to attach a header at all, which matters for scenarios a header can't cover, e.g. a session shared across sibling subdomains via a cookie scoped to a parent domain (`Domain=.example.com`).

This fallback is **off by default** and does not affect any existing project. It only activates once a project overrides `$cookieName` in its own `ApiControl` subclass — the same opt-in convention already used by `GetSecret()`:

```php
class AuthControl extends MagratheaApiControl {
    protected ?string $cookieName = "app_session";
}
```

If `$cookieName` is still `null` (the framework default), the cookie is never consulted and `Bearer`/`Basic` behave exactly as before. When a header **and** a matching cookie are both present, the header always wins.

Because `GetTokenInfo()` reads `$cookieName` off the instance handling the request, override it once on a shared base `ApiControl` class that every protected controller extends — not just on the login controller — so the fallback works consistently everywhere `$this->GetUserId()` / `$this->GetUserInfo()` is called.

### Issuing the cookie on login

`SetAuthCookie()` writes the session cookie, deriving its expiry from the JWT's own `exp` claim so the cookie never outlives (or underlives) the token:

```php
class AuthControl extends MagratheaApiControl {
    protected ?string $cookieName = "app_session";

    public function Login(): array {
        $post = $this->GetPost();
        // ... validate credentials ...

        $token = $this->jwtEncode([
            "user_id" => $user->id,
            "exp"     => time() + 86400,
        ]);

        $this->SetAuthCookie($token, ".example.com"); // share across subdomains
        return ["token" => $token]; // still returned in the body for non-cookie clients
    }
}
```

`SetAuthCookie(string $token, ?string $domain = null, bool $httpOnly = true, string $sameSite = "Lax", ?bool $forceSecure = null)` marks the cookie `Secure` by default — controlled by the `$forceSecureCookie` instance property (defaults to `true`), not by dev/prod mode. A `Secure` cookie is never sent by the browser over plain `http://localhost`, so for local HTTP development override `$forceSecureCookie` to `false` on your `ApiControl` subclass, or pass `forceSecure: false` for a one-off call.

### Clearing the cookie on logout

Because the cookie defaults to `HttpOnly`, client-side JS cannot delete it — logout needs a real server round-trip:

```php
class AuthControl extends MagratheaApiControl {
    // ...
    public function Logout(): array {
        $this->ClearAuthCookie(".example.com"); // domain must match SetAuthCookie()
        return ["success" => true];
    }
}
```

### Same name, same scope — sharing a cookie between APIs

A cookie's identity to the browser is the triple `(name, Domain, Path)`, not "which API set it." Two `ApiControl`s using **different** `$cookieName` values are fully isolated from each other, even under the same domain. Two using the **same** name **and** the same `Domain` passed to `SetAuthCookie()` are reading/writing the literal same cookie — that's what makes cross-subdomain session sharing work. Mixing the same name with mismatched `Domain` scopes is the one combination to avoid: the browser may store them as distinct cookies that still collide under a single key when PHP parses `$_COOKIE`, since duplicate cookie names in one request produce unpredictable "last one wins" behavior there.

---

## Token Best Practices

### Always set `exp` (expiration)

```php
$token = $this->jwtEncode([
    "user_id" => $user->id,
    "exp"     => time() + 3600, // expire in 1 hour
]);
```

### Store the secret safely in config

```ini
; magrathea.conf
[dev]
    jwt_key = "a-very-long-random-string-here"

[production]
    jwt_key = "$=JWT_SECRET"
```

`GetSecret()` already reads `jwt_key` from the active environment section by default — no override needed:

```php
public function GetSecret(): string {
    return \Magrathea2\Config::Instance()->Get("jwt_key");
}
```

Only override it if a project wants a different config key name.

### Never store sensitive data in the payload

JWT payloads are **signed, not encrypted**. Anyone can decode the payload. Only store:
- `user_id`
- `role`
- `exp`
- `iat`

Never store: passwords, full email, PII, financial data.

---

## Full Auth Flow

```
Client                    API
  |                         |
  |-- POST /auth/login ---→ |
  |   {email, password}     |  validates credentials
  |                         |  generates JWT
  |←-- {token: "eyJ..."} -- |
  |                         |
  |-- GET /profile --------→|
  |   Authorization:        |  decodes JWT
  |   Bearer eyJ...         |  reads user_id from payload
  |                         |  fetches user from DB
  |←-- {user data} -------- |
```

---

## Supported Algorithms

The default algorithm is `HS256` (HMAC-SHA256), controlled by `$jwtEncodeType` on `MagratheaApiControl`. To change it:

```php
class AuthControl extends MagratheaApiControl {
    public string $jwtEncodeType = "RS256";
}
```

Supported by `firebase/php-jwt`: `HS256`, `HS384`, `HS512`, `RS256`, `RS384`, `RS512`, `ES256`, `ES384`, `EdDSA`.
