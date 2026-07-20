# Logger — File-based Logging

**File:** `src/Logger.php`
**Namespace:** `Magrathea2`
**Extends:** `Singleton`

Writes log entries to files on disk. Supports custom log file names, path configuration, and error logging with stack traces.

---

## Configuration

Set the log directory in your config file:

```ini
[logs]
path = /var/log/myapp/
```

The log directory must be writable by the web server process.

---

## Properties

| Property | Type | Description |
|----------|------|-------------|
| `$logPath` | `string` | Directory where log files are stored |
| `$activeLogFile` | `string` | Current log file name (without path) |

---

## Methods

### `Initialize(): void`
Initialize the logger (loads path from config). Called automatically on first use.

### `LoadLogsPath(): Logger`
Reload the log path from config.

### `SetLogPath(string $p): Logger`
Override the log directory path programmatically.

```php
Logger::Instance()->SetLogPath("/custom/log/dir/");
```

### `GetLogPath(): string|null`
Returns the current log directory path.

### `SetLogFile(string $name): Logger`
Set the log file name (the file within the log directory). No need to add path or extension — just the base name.

```php
Logger::Instance()->SetLogFile("payments"); // logs to /var/log/myapp/payments.log
```

### `GetLogFile(): string`
Returns the current log file name.

### `GetFullLogFile(): string`
Returns the full absolute path to the log file.

### `Log(string $logThis): void`
Write a line to the log file. Throws `Exception` if the file is not writable.

```php
Logger::Instance()->Log("User 42 logged in from 192.168.1.1");
```

### `LogError(\Exception $error): void`
Write a formatted exception entry (message + stack trace) to the log.

```php
try {
    // risky operation
} catch (\Exception $e) {
    Logger::Instance()->LogError($e);
}
```

### `LogLastError(): void`
Log the last PHP error (from `error_get_last()`).

---

## Basic Usage

```php
use Magrathea2\Logger;

$logger = Logger::Instance();
$logger->Log("Application started");
$logger->Log("Processing request: " . $_SERVER["REQUEST_URI"]);
```

---

## Logging Exceptions

```php
use Magrathea2\Logger;

try {
    $result = Database::Instance()->Query($sql);
} catch (\Exception $e) {
    Logger::Instance()->LogError($e);
    // respond with error...
}
```

---

## Multiple Log Files

You can switch log files to organize logs by category:

```php
$logger = Logger::Instance();

// Write to payments.log
$logger->SetLogFile("payments")->Log("Payment processed: $orderId");

// Write to auth.log
$logger->SetLogFile("auth")->Log("Login failed for: $email");

// Back to default
$logger->SetLogFile("app")->Log("Something else happened");
```

---

## Integration with MagratheaPHP Modes

When you call `MagratheaPHP::Instance()->Prod()`, the framework redirects errors to the logger instead of displaying them. The logger becomes the primary visibility mechanism for production errors.

```php
// In production, exceptions bubble up to Logger automatically
MagratheaPHP::Instance()
    ->Prod()    // suppresses error display
    ->Load()
    ->Connect();
```

---

## Log File Format

Each `Log()` call appends a line in this format:

```
[2024-03-15 14:30:00] Your log message here
```

Error entries include the stack trace:

```
[2024-03-15 14:30:00] ERROR: Database connection failed
Stack trace:
#0 /src/DB/Database.php(42): mysqli->__construct(...)
#1 /src/MagratheaPHP.php(88): Database::OpenConnectionPlease()
...
```

---

## Notes

- Log files are appended to (not overwritten) on each write.
- The Logger is a singleton — share state across the request via `Logger::Instance()`.
- Log rotation is not managed by the framework — use OS tools like `logrotate` in production.
