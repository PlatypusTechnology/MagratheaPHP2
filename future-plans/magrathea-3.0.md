# MAGRATHEA 3.0

The time has come.
We're gonna completely rebuild Magrathea 2.x, to work directly with AI.

There's a lot of functionalities on the existing version of the framework which was designed to build a code writing, as we used to do.
Therfore, there are tools of CodeCreation, creating objects from tables, data manipulation, and so on, which doesn't need to be done manually anymore, and can be handled by an agent.

Magrathea 3 will be fully AI integrated and will use AI to write code.

## What we're dropping:
### Code Creator
We're not gonna create the code through Admin anymore. This means the `magrathea_objects.conf` will be dropped and the Object manipulator will be droped.
Instead, we will teach AI on how to create the base code. I still think that inheritance on the objects is a good idea, but I'm willing to change my opinion if you rather do everything in a single class. convince me.

### MagratheaAdmin Edit
We don't need to manipulate files manually, so drop the editor.

### Drepecated stuff
- remove `MagratheaModelControl::ShowAll()` (debug helper that echoes an HTML table; deprecated in 2.4.0 item 25)
- remove `MagratheaPHP::LoadVendor()` (redundant: the class can't load before `vendor/autoload.php`; deprecated in 2.4.0, found in item 5d)
- look for deprecated methods

## What we're adding:
### Setup Wizard
When the project is new, with no magrathea.conf file, we will have a setup wizard, the same way Wordpress Wizard works.
This setup wizard will create things like the `magrathea.conf` file — based on `magrathea.conf.sample`, the same files that exists today.
It will allow a database import, it will allow some structure verifications and some configuration verifications

### Database explorer
A default element on MagratheaAdmin.
As we have `Queries` feture today, we should have something to check on the database. Apart from queries, which should still be there.
Something like a Database explorer, where it's possible to view the tables, and for each table, view the columns and the type of columns.
We're gonna add all of this to a `Database` section, where itwill also be possible to generate dumps, import dumps, and run a query editor that will create queries based on Magrathea's Query service

### Timezone control
Timezone will also be added to the Setup Wizard
- timestamps owned by the database (default behavior): see below
- timezone extras (left out of 2.4.0 items 21/22, 2026-10-21):
  - Bootstrap wizard config form (`src/Bootstrap/views/steps/config-edit.php`): add a `timezone` field
    (saved to the environment section through `save-config`), ideally a select from
    `timezone_identifiers_list()`, so new projects can't misspell it.
  - admin `version.php` "Server Time" card duplicates the Server page (it prints the timezone and
    `Config::Get("timezone")`): replace it with a link to the Server page's "Timezone check".

### Timestamps owned by the database
Decided while working on 2.4.0 item 15 (2026-10-20). In 2.x, `Insert()` leaves `created_at`/`updated_at`
to MySQL (column default or trigger) but `Update()` writes `updated_at = now()` from PHP: two clocks,
and update triggers / `ON UPDATE CURRENT_TIMESTAMP` are always overridden.

### Some changed behaviours:
- `Insert()` keeps not sending unset timestamps (DB default or trigger sets them).
- `Update()` stops writing `updated_at`: `ON UPDATE CURRENT_TIMESTAMP` (or a `BEFORE UPDATE` trigger)
  sets it. `created_at` is still never written.
- The object doesn't invent values: after `Insert()`/`Update()` the timestamps are read back from the
  row (one `SELECT created_at, updated_at … WHERE pk = ?`), or left `null` (decide which; read-back
  keeps `ToJson()` create/update responses filled).

### Things to settle before implementing:
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




for now, we're gonna discuss the implementation and elaborate a `plan-3.0.md`
We're also gonna elaborate a migration plan, that will work from versions `2.3.*` and from version `2.4.*`
The migration plan will be directed to the Agents, who will apply the migration by themselves. The sensitive points of the migrations should be highlighted and the agents will take care of them.

