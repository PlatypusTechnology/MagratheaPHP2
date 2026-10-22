
- database explorer:
  - view tables, and column for each table
  - query editor
  - run query
  - generate dump
  - import from dump
- log explorer
- error log explorer
- remove Magrathea Code Generator
- remove magrathea_objects.conf
- remove MagratheaAdmin Edit feature
- remove `MagratheaModelControl::ShowAll()` (debug helper that echoes an HTML table; deprecated in 2.4.0 item 25)
- remove `MagratheaPHP::LoadVendor()` (redundant: the class can't load before `vendor/autoload.php`; deprecated in 2.4.0, found in item 5d)
- look for deprecated methods

## future 3.0 implementations:
- sentry integration
- timestamps owned by the database (default behavior): see below
- timezone extras (left out of 2.4.0 items 21/22, 2026-10-21):
  - Bootstrap wizard config form (`src/Bootstrap/views/steps/config-edit.php`): add a `timezone` field
    (saved to the environment section through `save-config`), ideally a select from
    `timezone_identifiers_list()`, so new projects can't misspell it.
  - admin `version.php` "Server Time" card duplicates the Server page (it prints the timezone and
    `Config::Get("timezone")`): replace it with a link to the Server page's "Timezone check".

## Timestamps owned by the database

Decided while working on 2.4.0 item 15 (2026-10-20). In 2.x, `Insert()` leaves `created_at`/`updated_at`
to MySQL (column default or trigger) but `Update()` writes `updated_at = now()` from PHP: two clocks,
and update triggers / `ON UPDATE CURRENT_TIMESTAMP` are always overridden.

**3.0 default:**
- `Insert()` keeps not sending unset timestamps (DB default or trigger sets them).
- `Update()` stops writing `updated_at`: `ON UPDATE CURRENT_TIMESTAMP` (or a `BEFORE UPDATE` trigger)
  sets it. `created_at` is still never written.
- The object doesn't invent values: after `Insert()`/`Update()` the timestamps are read back from the
  row (one `SELECT created_at, updated_at … WHERE pk = ?`), or left `null` (decide which; read-back
  keeps `ToJson()` create/update responses filled).

**Things to settle before implementing:**
- **Schema requirement:** every `updated_at` column needs `ON UPDATE CURRENT_TIMESTAMP`. Without it,
  `updated_at` silently stops changing after the insert. The 3.0 migration doc needs a
  `SHOW CREATE TABLE` check, and maybe an admin tool that lists tables missing it.
- **"Touch" semantics are lost:** MySQL only fires `ON UPDATE` when some column value actually changes,
  so `Save()` on an unchanged object no longer bumps `updated_at`. Decide whether to offer an explicit
  `Touch()` (`SET updated_at = CURRENT_TIMESTAMP`, DB clock).
- **Partial updates (2.4.0 item 16):** "nothing changed → write only `updated_at`" becomes an empty
  `SET` (SQL error). Nothing changed must mean no query (or `Touch()`).
- **Opt-out:** a model flag to keep the 2.x PHP-clock behavior for tables that can't get `ON UPDATE`?
- **Imports:** assigned timestamps are replaced with `now()` on insert today; 3.0 could send an assigned
  value as is (only `null` left to the DB), so `InsertWithPk()` imports keep original timestamps.
- Generator/admin SQL already creates `DEFAULT CURRENT_TIMESTAMP` / `ON UPDATE CURRENT_TIMESTAMP`;
  `src/Admin/database.sql` mixes `TIMESTAMP` (`created_at`) and `DATETIME` (`updated_at`): pick one.
