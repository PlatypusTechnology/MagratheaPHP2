# Singleton — Base Singleton Pattern

**File:** `src/Singleton.php`
**Namespace:** `Magrathea2`
**Type:** Abstract Class

The base class for all manager/service classes in the framework. Ensures a class can only have one instance per process. Most framework classes (`Config`, `Database`, `Logger`, `Debugger`, `MagratheaCache`, etc.) extend `Singleton`.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$instance` | `protected static array\|null` | Stores instances per class name |

---

## Methods

### `Instance(): static`
Returns the single shared instance of the calling class. Creates it on the first call.

```php
use Magrathea2\Config;

$config = Config::Instance();
// Always the same object
$config === Config::Instance(); // true
```

### `MockClass($mocker): static`
Replaces the instance with a mock/stub object. Used for testing.

```php
$mock = new FakeDatabase();
Database::MockClass($mock);
// Now Database::Instance() returns $mock
```

### `SetInstance($inst): void`
Directly sets the stored instance. Lower-level alternative to `MockClass`.

---

## Constructor & Cloning

All these are intentionally restricted:

```php
final private function __construct()  // can't be manually instantiated
final protected function __clone()    // can't be cloned
final public function __wakeup()      // can't be unserialized
```

This ensures that only `Instance()` can create the object.

---

## Creating Your Own Singleton

```php
<?php
namespace App\Services;

use Magrathea2\Singleton;

class MyService extends Singleton {
    private string $data = "";

    public function SetData(string $d): static {
        $this->data = $d;
        return $this;
    }

    public function GetData(): string {
        return $this->data;
    }
}
```

```php
MyService::Instance()->SetData("hello");
echo MyService::Instance()->GetData(); // "hello"
```

---

## Notes

- The `$instance` array is keyed by class name (via `static::class`), so subclasses each get their own instance.
- `MockClass` is especially useful in unit tests when you want to swap in a fake implementation.
