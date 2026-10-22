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
| `Debugger::LOG` | 2 | Exceptions go to `Logger::LogError()`, other items to `Logger::Log()` (default) |
| `Debugger::DEBUG` | 3 | Items are stored, and printed with `Show()` at the end of the request (not after a JSON response). Inside an API call they go to the response's `_debug` instead (see below) |
| `Debugger::DEV` | 4 | Items are printed as `<pre>` where they're added (and stored). Inside an API call they go to the response's `_debug` instead (see below) |
| `Debugger::ANALYSIS` | 5 | Items are ignored; API error responses carry a `stacktrace` |

Every `MagratheaException` adds itself to the Debugger when it's created, even if it's caught later:
in DEV each one is printed (or goes to `_debug`), in LOG each one is logged, once.

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

## DEV and DEBUG Inside API Calls (`_debug`)

DEV's `<pre>` output, and the HTML that DEBUG prints at the end of the request, would break a JSON
response, so `MagratheaApi::ExecuteUrl()` (and so `Run()`) wraps each call in a capture (since 2.4.0).
While capturing, DEV and DEBUG collect what `Add()`, `AddError()`, `AddQuery()` and `Info()` receive instead
of printing it, and `MagratheaApi::Json()` adds it to the response as `_debug`. In DEBUG, `_debug` starts
with the items stored before the call (bootstrap), since nothing is printed after a JSON response:

```json
{"success": true, "data": {...}, "_debug": [
  {"type": "Magrathea2\\Exceptions\\MagratheaException", "message": "caught one", "code": 42, "file": "/var/www/app/Api/X.php:40", "data": {"id": 7}},
  "Processing user #42"
]}
```

Outside API calls (pages, CLI scripts) DEV and DEBUG print as before. Other modes collect nothing. See
[MagratheaApi](../api/magrathea-api.md) for which responses get `_debug`.

### `StartCapture(): Debugger` / `StopCapture(): Debugger`
Start and end a capture. Calls nest: only the outermost pair starts the collection and drops it at the end.

### `IsCapturing(): bool`
Whether a capture is running.

### `GetCaptured(): array`
The collected items, ready to be JSON-encoded: exceptions as `{type, message, code, file, data?, query?, values?}`
(`query`/`values` for `MagratheaDBException`), anything else as it was added. The same exception object is
collected once.

### `WithCaptured(mixed $response): mixed`
Returns `$response` with `_debug` added, if something was collected (in DEBUG: or stored) and `$response`
is an associative array. Lists, scalars and objects come back unchanged. If `_debug` can't be JSON-encoded
(invalid UTF-8, recursion), it holds an error message instead, so the response is still sent.

---

## Displaying Debug Info

### `Show(): void`
Output all collected debug items.

```php
Debugger::Instance()->Show();
```

### `ShowAtShutdown(?array $headers = null): void`
Called by the framework's shutdown function: in DEBUG it calls `Show()`, unless the response's headers
(default: `headers_list()`) have a JSON `Content-Type`, where the HTML would break the body. Other modes
print nothing. (In the CLI `headers_list()` is always empty, so DEBUG CLI scripts always print.)

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

// Debug() → Debugger::DEBUG, query logging ON
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
