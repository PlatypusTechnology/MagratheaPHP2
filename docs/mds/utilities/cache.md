# MagratheaCache — Response Caching

**File:** `src/MagratheaCache.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

File-based response cache. Stores successful API responses as JSON files, so a repeated request is answered straight from disk without running the action again (no database queries, no computation).

It's built around the API: in a controller you call `$this->Cache(...)` once, and the framework does the rest. There is no TTL — a cached response is served until it's cleared.

---

## Configuration

Both keys live in the **environment section** (`[dev]`, `[production]`, …) of `configs/magrathea.conf`, not in a section of their own:

```ini
[production]
	cache_path = "/var/www/myapp/cache"
	; no_cache = true   ; turns caching off: Cache() does nothing
```

- `cache_path` must be an existing directory. It's resolved with `realpath()`; an empty or invalid path throws a `MagratheaException` on first use (`"[cache_path] is empty"` / `"cache path is invalid: ..."`).
- The web server needs write permission on the **directory** itself, not just on the files in it (see `SaveFile()`).
- `no_cache = true` disables caching: `Cache()` neither serves nor saves anything. Useful in development. Raw-mode API calls (`SetRaw()`, `Run(true)`) bypass the cache whatever the config says.

---

## How it works

1. `Cache($name, $data)` builds the cache key, and if a file for it exists, **outputs it and ends the script** (`die`). Otherwise it marks the request as "to be saved" and returns.
2. The action runs normally.
3. When `MagratheaApi` emits the successful response (`ReturnSuccess()`), it calls `HandleApiCache()`, which saves the response to the cache file, outputs it, and ends the script.

Only successful responses are cached. Errors and failures (`ReturnError()`, `ReturnFail()`, exceptions) are never written to the cache.

The cached file holds the response with `"cached": true` and `"cached_time"` added. The response that created the cache is sent with `"cached": false` (and the same `cached_time`), so clients can tell a hit from a miss.

---

## Usage in API controllers

This is the normal way to use the cache. `MagratheaApiControl` has three helpers:

| Method | Does |
|--------|------|
| `Cache($name, $data = null)` | `MagratheaCache::Instance()->Type("json")->Cache($name, $data)` |
| `CacheClear($name, $data = null)` | `MagratheaCache::Instance()->Type("json")->Clear($name, $data)` |
| `CacheClearPattern($pattern)` | `MagratheaCache::Instance()->RemovePattern($pattern)` |

`$data` is **not** the response: it's an optional key suffix (an id, a page number). The key becomes `"{$name}-{$data}"`. Never pass the computed result or a JSON string as `$data`.

In DEV (`MagratheaPHP::Instance()->Dev()`), `Cache()` writes `adding cache for [name/data] from (... ->Action)` to the `Logger` on every call, cache hits included. Outside DEV (`Debug()` and `Prod()` too) it doesn't log. Before 2.4.0 it logged in every environment.

```php
class ProductApiControl extends MagratheaApiControl {

    public function List(): array {
        // Hit: outputs the cached response and ends the script.
        // Miss: returns; the response below is saved automatically.
        $this->Cache("products_all");

        $products = ProductControl::GetAll();
        return array_map(fn($p) => $p->ToArray(), $products);
    }

    public function View(array $params = []): array {
        // one cache file per product: "product-<id>.json"
        $this->Cache("product", $params["id"]);

        $product = new Product($params["id"]);
        return $product->ToArray();
    }

    public function Create($data = []): array {
        $product = new Product();
        $product->Assign($this->GetPost());
        $product->Save();

        // Invalidate what this change made stale
        $this->CacheClear("products_all");
        $this->CacheClearPattern("products_category_*");

        return $product->ToArray();
    }
}
```

---

## Properties

| Property | Visibility | Type | Default | Description |
|----------|-----------|------|---------|-------------|
| `$cacheName` | public | `string\|null` | `null` | Current cache key (set by `Cache()`) |
| `$saveCache` | public | `bool` | `false` | Set to `true` by `Cache()` on a miss; `HandleApiCache()` only saves when it's `true` |
| `$cachePath` | private | `string` | | Resolved cache directory |
| `$extension` | private | `string` | `"json"` | Cache file extension (see `Type()`) |
| `$bypass` | private | `bool` | `false` | Caching is off (see `Bypass()`) |

---

## Methods

### `Cache(string $name, mixed $data = null)`
Builds the cache key from `$name` and the optional `$data` suffix, then calls `LookForFile()`:
- cache file exists → outputs it as JSON and **ends the script**;
- no file → sets `$saveCache = true` and returns.

If `no_cache` is on, or the cache is bypassed (see `Bypass()`), it returns `false` right away and sets nothing. `$data` can't be an object (throws `MagratheaException`). `/` and `\` in the key are replaced with `|`, so a key never points outside the cache directory.

### `Clear(string $name, mixed $data = null): bool`
Deletes the cache file for the key built from `$name`/`$data`. Returns `true` if the file didn't exist, otherwise `unlink()`'s result. It doesn't change the current key, so an action can call `Cache()` and then clear other keys. (Before 2.4.0 it replaced the current key, and the response was saved under the cleared one.)

```php
MagratheaCache::Instance()->Clear("product", 42); // deletes product-42.json
```

### `RemovePattern(string $pattern): array`
Deletes every file in the cache directory that matches a `glob()` pattern. The pattern is matched against file names as they are on disk, extension included. Returns the full paths of the deleted files.

```php
MagratheaCache::Instance()->RemovePattern("products_*");
// Deletes: products_all.json, products_category_3.json, ...
```

### `RemoveAllCache(): array`
`RemovePattern('*')`: deletes **all** files in the cache directory, including leftover `*.tmp` files. Returns the full paths of the deleted files. It's what the admin's Cache feature uses for "clear all".

### `DeleteFile(string $file, bool $addExtension = true): bool`
Deletes a cache file by file name (appending the current extension unless `$addExtension` is `false`). Logs the deletion to the admin log and the `Logger`. Returns `false` if the file couldn't be deleted.

### `Bypass(bool $bypass = true): MagratheaCache`
Turns caching off (`true`) or back on (`false`) for the rest of the process. While bypassed, `Cache()` neither serves nor saves, as with `no_cache`, and a pending save is cancelled. `MagratheaApi::ExecuteUrl()` sets it on every call: bypassed in raw mode, active otherwise.

### `Type(string $t): MagratheaCache`
Sets the file extension used to build the cache file name (default `"json"`). It doesn't change the output: hits are always sent as `application/json`.

### `LookForFile()`
If the cache file for the current key exists, outputs its content with `ShowJson()` and ends the script. Returns `false` otherwise.

### `GetCacheFile(): string`
Full path of the cache file for the current key: `{cache_path}/{cacheName}.{extension}`.

### `SaveFile(string $data): int|false`
Write string data to the cache file. Returns the number of bytes written, or `false` if the file
couldn't be written (e.g. the cache directory isn't writable).

The write is atomic: the data goes to a temp file (`{name}.{extension}.{pid}.{random}.tmp`) in the
cache directory, which is then renamed over the cache file. A concurrent request reading the cache
sees either the old file or the new one, never an empty or half-written one. On failure, the temp
file is deleted and the existing cache file is left as it was.

### `HandleApiCache(array $data)`
Called by `MagratheaApi` with every successful response. If `Cache()` armed it (`$saveCache` is `true` and there's a key), it saves `$data` with `"cached": true` and `"cached_time"`, then outputs it with `"cached": false` and **ends the script**. Otherwise it returns `false` and the API outputs the response as usual.

### `ShowJson(array|string $data)`
Sends a `Content-Type: application/json` header, outputs `$data` (a string as is, an array JSON-encoded) and **ends the script**. It doesn't save anything.

### `LoadCachePath(): MagratheaCache`
Reads `cache_path` from the config and resolves it. Throws `MagratheaException` if it's empty or doesn't exist. Called automatically on first use.

### `GetCachePath(): string|null`
Returns the resolved cache directory, loading it from the config if needed.

---

## Direct usage (outside a controller)

Without `MagratheaApi`, `HandleApiCache()` has to be called by hand:

```php
use Magrathea2\MagratheaCache;

$cache = MagratheaCache::Instance();

// Hit: outputs the cached JSON and ends the script
$cache->Cache("expensive_report");

$data = compute_heavy_report();

// Saves and outputs the response, then ends the script.
// Returns false only when caching is off (no_cache), so output it yourself then.
if($cache->HandleApiCache($data) === false) {
    $cache->ShowJson($data);
}
```

---

## Cache key naming tips

Include every parameter that changes the result. Use `$data` for a single discriminator (id, page) and bake the rest into `$name`:

```php
// Good
$this->Cache("products_category_{$categoryId}", $page);
$this->Cache("user_profile", $userId);

// Avoid: every caller shares one file
$this->Cache("data");
```

Name related keys with a common prefix, so `CacheClearPattern("products_*")` can invalidate the whole group when the data changes.

---

## Notes

- There's no TTL: cache files persist until they're cleared (`CacheClear()`, `CacheClearPattern()`, `RemoveAllCache()`, or the admin's Cache feature).
- Raw mode (`Run(true)`, `SetRaw()`) bypasses the cache: a cached action always runs and its data is returned, and nothing is read from or written to the cache. So tests calling the API in raw mode get fresh data, and you can't warm the cache through raw calls.
- Because `SaveFile()` writes through a temp file and a rename, the web server needs write
  permission on the cache **directory**, not just on the files in it. An overwritten cache file
  belongs to the user that wrote it last.
- A process killed in the middle of a write (OOM killer, `SIGKILL`, `max_execution_time` hit
  during the write) can leave a `*.tmp` file behind. It's never served as a cache hit, and
  `RemoveAllCache()` (the admin's "clear all cache") deletes it.
- The cache is file-based: no Redis, Memcached or any other service needed.
