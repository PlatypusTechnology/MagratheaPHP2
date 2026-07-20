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
[jwt]
secret = your-very-long-random-secret-here
```

```php
// Override GetSecret() in your control
public function GetSecret(): string {
    return \Magrathea2\Config::Instance()->GetConfig("jwt/secret");
}
```

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
