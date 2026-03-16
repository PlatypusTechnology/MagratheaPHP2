# Debugger — Debug Mode Manager

**File:** `src/Debugger.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

Central debug management for the framework. Controls verbosity of error output, query logging, and stack trace display. Integrates with `MagratheaPHP`'s mode system.

---

## Debug Levels

| Constant | Value | Description |
|----------|-------|-------------|
| `Debugger::NONE` | 0 | No debug output |
| `Debugger::LOG` | 2 | Log to file only (default) |
| `Debugger::DEBUG` | 3 | Verbose debug with stack traces |
| `Debugger::DEV` | 4 | Full development mode |

---

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$logFile` | `?string` | `null` | Path to the debug log file |
| `$debugItems` | `array` | `[]` | Collected debug entries |
| `$debugType` | `int` | `LOG` | Current debug level |
| `$queries` | `bool` | `false` | Whether query logging is enabled |

---

## Setting the Debug Level

### `SetType(int $type): Debugger`
Set the debug level manually.

```php
use Magrathea2\Debugger;
Debugger::Instance()->SetType(Debugger::DEV);
```

### `SetDev(): Debugger`
Enable development mode (level `DEV`).

```php
Debugger::Instance()->SetDev();
```

### `SetDebug(): Debugger`
Enable debug mode (level `DEBUG`).

```php
Debugger::Instance()->SetDebug();
```

### `GetType(): int`
Returns the current debug level integer.

### `GetTypeDesc(): string`
Returns the current level as a human-readable string.

---

## Query Logging

### `LogQueries(bool $q): Debugger`
Enable or disable SQL query logging.

```php
Debugger::Instance()->LogQueries(true);
```

When enabled, every SQL query executed via `Database` is recorded and shown in the debug output.

---

## Adding Debug Items

### `Add(mixed $debug): void`
Add any value to the debug collection (string, array, object).

```php
Debugger::Instance()->Add("Processing user #42");
Debugger::Instance()->Add(["step" => "validation", "passed" => true]);
```

### `Info(string $debug): void`
Add an info-level string to the debug collection.

```php
Debugger::Instance()->Info("Cache miss for: products_list");
```

### `AddError(\Exception $err): void` / `Error(\Exception $err): void`
Record an exception.

```php
try {
    // something risky
} catch (\Exception $e) {
    Debugger::Instance()->AddError($e);
}
```

### `AddQuery(string $sql, ?string $values): void`
Manually add a query to the debug log.

```php
Debugger::Instance()->AddQuery($sql, json_encode($values));
```

---

## Displaying Debug Info

### `Show(): void`
Output all collected debug items.

```php
Debugger::Instance()->Show();
```

### `Trace(): void`
Print the current stack trace.

```php
Debugger::Instance()->Trace();
```

---

## Log File

### `SetLogFile(string $file): Debugger`
Set a file path for writing debug output.

```php
Debugger::Instance()->SetLogFile("/var/log/myapp/debug.log");
```

---

## Temporary Level Switching

Useful when you need to temporarily increase verbosity for a specific operation:

### `SetTemp(string $dType): Debugger`
Temporarily switch to a different debug type.

### `BackTemp(): Debugger`
Restore the previous debug type.

```php
$debugger = Debugger::Instance();
$debugger->SetTemp("debug");
// ... do something with more logging ...
$debugger->BackTemp();
```

---

## Integration with MagratheaPHP Modes

`MagratheaPHP` methods configure `Debugger` automatically:

```php
// Dev() → Debugger::DEV, query logging off
MagratheaPHP::Instance()->Dev();

// Debug() → Debugger::DEV, query logging ON
MagratheaPHP::Instance()->Debug();

// Prod() → Debugger::LOG, query logging off
MagratheaPHP::Instance()->Prod();
```

---

## Example: Request Debug Dump

```php
use Magrathea2\Debugger;

// At the end of a request (dev only)
if (Debugger::Instance()->GetType() >= Debugger::DEV) {
    Debugger::Instance()->Show();
}
```

---

## Notes

- `Debugger` collects items in memory during a request — `Show()` outputs them all at once.
- In production, debug output should never be shown to end users; use the `Logger` instead.
- Query logging (`LogQueries`) can significantly impact performance — use only in dev/debug modes.
