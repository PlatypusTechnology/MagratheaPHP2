# TimezoneCheck — Timezone Sync and Troubleshooter

**File:** `src/TimezoneCheck.php`
**Namespace:** `Magrathea2`
**Type:** Static utility class (all methods are `static`)
**Since:** 2.4.0

Decides whether MySQL's session timezone follows PHP's (`db_sync_timezone`), and checks the
timezone setup for the admin and the Bootstrap wizard. The troubleshooter (`Run()`) is only called
from the admin's Server page and the wizard's config step, never on a normal request.

---

## Config keys

Both go in the **environment** section (`[dev]`, `[production]`...):

```ini
[production]
	timezone = "America/Sao_Paulo"
	; db_sync_timezone = false     ; uncomment to stop syncing MySQL's time_zone
```

- `timezone`: PHP's timezone, set by `MagratheaPHP::Load()`. Not `time_zone`: that key was in older
  samples and was never read.
- `db_sync_timezone`: on when absent. `false`, `off`, `no`, `0` or an empty value, quoted or not, turn
  it off; any other value keeps it on. With no
  `timezone`, the sync is off whatever this says.

How the sync itself works (the `SET time_zone`, the offset fallback, what it doesn't cover) is on the
Database page, "Timezone sync".

---

## Constants

| Constant | Value | Meaning |
|---|---|---|
| `SYNC_ON` | `"on"` | sync on |
| `SYNC_OFF` | `"off"` | `db_sync_timezone` is off |
| `SYNC_NO_TIMEZONE` | `"no-timezone"` | off because the config has no `timezone` |
| `OK`, `INFO`, `WARNING`, `ERROR` | `"ok"`, `"info"`, `"warning"`, `"error"` | finding levels |

---

## Methods

### `SyncState(): string`
`SYNC_ON`, `SYNC_OFF` or `SYNC_NO_TIMEZONE`, from the active environment's config.
`MagratheaPHP::Connect()` uses it to call `Database::SetTimezoneSync()`.

### `Run(): array`
Runs the troubleshooter and returns a list of findings, each `["level" => ..., "message" => ...]`
(messages use backticks for code). It never throws: a database it can't query is a `WARNING` finding.
It reports:

- `time_zone` in the config without `timezone` → `WARNING`: the key is ignored; renaming it sets PHP's
  timezone **and** turns on the MySQL sync;
- no timezone key at all → `INFO`;
- an invalid `timezone` (not a PHP identifier, backward-compatible names like `Brazil/East`
  included) → `ERROR`. It's only reported: at runtime PHP keeps its default zone;
- PHP's zone and `now()`, MySQL's `@@global.time_zone`, `@@session.time_zone` and `NOW()` (`INFO`),
  then `OK` "in sync" or `ERROR` "out of sync", comparing their UTC offsets;
- the sync state: on (with the zone it set, `OK`), on with the offset fallback (`WARNING`: MySQL's
  timezone tables aren't loaded), or off and why (`INFO`).

```php
use Magrathea2\TimezoneCheck;

foreach (TimezoneCheck::Run() as $finding) {
    echo strtoupper($finding["level"]).": ".$finding["message"]."\n";
}
```

Run it after `Load()` and `Connect()`: it uses `Database::Instance()`, and its query opens the
connection (which is when the sync runs).
