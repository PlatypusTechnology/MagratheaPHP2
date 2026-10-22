# Global Functions & Autoloader

**Files:** `src/_Functions.php`, `src/_FunctionsDebug.php`
**Namespace:** Global (no namespace)

These files define procedural helper functions available globally (without `use` or namespace prefix) and register the framework's custom autoloader (not PSR-4: it goes by class name, not by namespace path).

---

## Autoloader

MagratheaPHP2 uses a custom `spl_autoload_register` callback. It's registered as soon as `vendor/autoload.php` is required (Composer loads `src/_Functions.php` as a `files` autoload), and it scans all registered code folders to resolve class names.

### How it works

1. Requiring `vendor/autoload.php` registers the autoloader (`src/_Functions.php`).
2. When PHP encounters an unknown class `App\Models\User`, the autoloader takes the class's basename (`User`) and looks for `User.php` in **every** registered folder. The namespace doesn't choose the folder.
3. One match: that file is loaded.
4. Several matches (two `User.php` in different folders): it loads the one whose `namespace` declaration matches the requested class's namespace (`App\Models`, case-insensitive; a class with no namespace matches a file with no `namespace`). If none matches, the first folder's file is loaded, as before 2.4.0. In DEV, a debug item lists the files and the one loaded. The same file reached through two folder entries (e.g. `AddFeature("User")` plus `AddRootCodeFolder("features/User")`) counts once.
5. No match: the autoloader returns without loading anything (since 2.4.0; it threw `MagratheaException` for app classes before), so `class_exists()` returns `false`, autoloaders registered after it still run, and `new X()` fails with PHP's own `Error: Class "X" not found`. In DEV, a class within the app namespace (`MagratheaPHP::AppNamespace()`) adds a "Could not find class [...]. Are the code folders correct?" debug item listing the folders.
6. Folders are registered with `AddCodeFolder()` (paths used as given: a relative one resolves against the current working directory, not the app root), `AddRootCodeFolder()` (relative to the app root) or `AddFeature()` (`<appRoot>/features/<Name>` and its `Base`).

```php
MagratheaPHP::Instance()
    ->AppPath(__DIR__)
    ->AddRootCodeFolder("models", "controls", "services", "api"); // <appRoot>/models, ...
```

Keep class basenames unique across code folders anyway: every lookup of a duplicated name reads the candidate files, and the DEV warning shows up on every request that loads it.

---

## Global Functions

### `now(): string`
Returns the current date/time formatted as `"Y-m-d H:i:s"`.

```php
echo now(); // "2024-03-15 14:30:00"
```

Useful for setting `created_at` / `updated_at` timestamps without importing a class.

---

### `arrToStr(array $array): string`
Converts an array to a one-line string of `[key] = "value"` pairs, separated by `, `. Values are cast to string, so nested arrays print as `Array`.

```php
$arr = ["a" => 1, "b" => 2];
echo arrToStr($arr);
// [a] = "1", [b] = "2"
```

---

### `p_r(mixed $debugme): void`
Pretty-prints a value wrapped in `<pre>` tags. Equivalent to `echo "<pre>" . print_r($debugme, true) . "</pre>"`.

```php
$user = UserControl::GetWhere(["id" => 1])[0];
p_r($user);
```

---

### `isMagratheaModel(mixed $object): bool`
Returns `true` if the given value is an instance of `MagratheaModel`.

```php
if (isMagratheaModel($obj)) {
    echo $obj->ToJson();
}
```

---

### `getClassNameOfClass(string $fullClassName): string`
Strips the namespace from a fully-qualified class name and returns just the class name.

```php
echo getClassNameOfClass("App\\Models\\User"); // "User"
echo getClassNameOfClass("Magrathea2\\DB\\Database"); // "Database"
```

---

### `fileDeclaresNamespace(string $file, string $namespace): bool`
Returns `true` if the PHP file has a `namespace` declaration equal to `$namespace` (case-insensitive; any of them, if the file has several blocks). `""` means the global namespace: it matches a file with no `namespace` declaration. Unreadable files return `false`. Used by the autoloader to choose between files with the same name.

```php
fileDeclaresNamespace(__DIR__."/models/User.php", "App\\Models"); // true
```

---

### `magrathea_getTypesArr(): array`
Returns the array of data types supported by the ORM field system:

```php
$types = magrathea_getTypesArr();
// ["int", "boolean", "string", "text", "float", "decimal", "datetime", "date"]
```

Used internally by `MagratheaModel` when validating `$dbValues` field type declarations.

---

## Debug Functions (`_FunctionsDebug.php`)

These are additional debug helpers loaded alongside the main functions file.

### `magrathea_debug(mixed $data): void`
Adds an item to the current `Debugger` instance for display.

### `magrathea_log(string $message): void`
Logs a message via the `Logger` singleton.

---

## Notes

- All global functions are unconditionally defined when `_Functions.php` is included. There are no conditional guards — avoid name collisions in your own code.
- `p_r()` outputs HTML, so it should only be used in web/CLI debug contexts, not in JSON API responses.
