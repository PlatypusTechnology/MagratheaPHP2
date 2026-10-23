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

> **Settled (2026-10-23):** one class per model and per control for new code (plan-3.0 item 4). The
> Base layer only protected hand-written code from the generator, and the generator is gone.
> Inheritance from `MagratheaModel`/`MagratheaModelControl` stays. Existing Base-split code keeps
> working, and the migration only strips the "generated" headers.

### MagratheaAdmin Edit
We don't need to manipulate files manually, so drop the editor.

### Drepecated stuff
- remove `MagratheaModelControl::ShowAll()` (debug helper that echoes an HTML table; deprecated in 2.4.0 item 25)
- remove `MagratheaPHP::LoadVendor()` (redundant: the class can't load before `vendor/autoload.php`; deprecated in 2.4.0, found in item 5d)
- look for deprecated methods

> **Settled (2026-10-23):** also removed (plan-3.0 item 3): `Query::Clean()`, `MagratheaApi::Start()`,
> `MagratheaApiAuth::ResponseUserPayload()`, `Database::CloseConnectionThanks()`,
> `MagratheaModel::IncludeAllModels()`, `GetNextID()` and `GetAutoLoad()`. `StartDB()` is deprecated
> and goes in 4.0.

## What we're adding:
### Setup Wizard
When the project is new, with no magrathea.conf file, we will have a setup wizard, the same way Wordpress Wizard works.
This setup wizard will create things like the `magrathea.conf` file — based on `magrathea.conf.sample`, the same files that exists today.
It will allow a database import, it will allow some structure verifications and some configuration verifications

> **Settled (2026-10-23)** (plan-3.0 items 22–23):
> - **Entry:** a dedicated file, `public/setup.php`, in the project skeleton. `MagratheaPHP::Load()`
>   is unchanged; its "config missing" page points to the wizard.
> - **Lock:** an 8-digit token, `xxxx-xxxx`, written to `configs/.setup-token`, so only someone with
>   file access can read it.
>   - 10 failed tries lock the wizard until the owner deletes the lock file.
>   - With a config present, re-entering also needs `bootstrap_unlocked = true`.
>   - The token is deleted when setup finishes.
> - **Name:** it keeps the `Bootstrap` name.
> - **Password reset:** its admin password reset moves to the CLI (`admin:reset-password`).

### Database explorer
A default element on MagratheaAdmin.
As we have `Queries` feture today, we should have something to check on the database. Apart from queries, which should still be there.
Something like a Database explorer, where it's possible to view the tables, and for each table, view the columns and the type of columns.
We're gonna add all of this to a `Database` section, where itwill also be possible to generate dumps, import dumps, and run a query editor that will create queries based on Magrathea's Query service

> **Settled (2026-10-23)** (plan-3.0 items 12–21):
> - **Query editor:** a structured form that builds a real `Query` object and shows its PHP chain and
>   SQL, with no `eval`. The 2.4.0 security update removes the old `eval()` Query Tester.
> - **Dumps:** `mysqldump`/`mysql` through `proc_open()`, with a streaming PHP fallback for hosts
>   without exec.
> - **Checks page:** lists timestamp-schema problems, each with the ALTER that fixes it, and model ↔
>   table drift.

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

> **Settled (2026-10-23):** read back (plan-3.0 items 8–9).

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

> **Settled (2026-10-23)** (plan-3.0 Group C):
> - **Schema requirement:** `SchemaCheck` lists every table missing `DEFAULT` or
>   `ON UPDATE CURRENT_TIMESTAMP`, each with the ALTER that fixes it, in the admin's Checks page and
>   in `magrathea check`. The migration applies them as a ⚠ step.
> - **Touch:** `Touch()` is added.
> - **Partial updates:** nothing changed means no query.
> - **Opt-out:** no flag.
> - **Imports:** `Insert()` sends assigned timestamps as they are. `Update()` never writes either
>   one; explicit values go through `Query::Update()`.
> - **Type:** `DATETIME` in the framework's SQL. Existing `TIMESTAMP` columns aren't converted.
> - **Every model table must have `created_at`/`updated_at`.** It was already required, and now
>   it's documented and checked.

### Agent CLI (settled 2026-10-23)
Agents work in a shell, not a browser. The checks, schema, drift and dumps behind the `Database`
section and the wizard are framework classes that return arrays. They're also exposed as
`vendor/bin/magrathea`: `check`, `db:describe --json`, `models:verify`, `db:dump`/`db:import`,
`admin:reset-password`. See plan-3.0 Groups D and G.

### Model defaults (settled 2026-10-23)
Without the generator, agents write models by hand, so `MagratheaModel` provides what the generator
used to write:
- a constructor and `$autoload`;
- a default `GetControl()`;
- one list of field types, where unknown types throw.

Controls can declare `$model = X::class`. `$strictTypes` defaults to `true`, and the migration pins
`false` on existing models. See plan-3.0 Group B.

### AI docs and packaging (settled 2026-10-23)
- `skills.MD` is rewritten for 3.0 only: it's effectively the code generator now.
- `instructions.MD` is replaced by `AGENTS.md`, a guide for working on the framework itself, which
  doesn't ship.
- `docs/mds/**` ships in `vendor/`, so agents get the migration guides and the docs offline.
- `CLAUDE.md`, `future-plans/`, `phpunit.phar`, `scripts/` and the logos stop shipping.

See plan-3.0 Group H.




for now, we're gonna discuss the implementation and elaborate a `plan-3.0.md`
We're also gonna elaborate a migration plan, that will work from versions `2.3.*` and from version `2.4.*`
The migration plan will be directed to the Agents, who will apply the migration by themselves. The sensitive points of the migrations should be highlighted and the agents will take care of them.

> **Done (2026-10-23):** `future-plans/plan-3.0.md` and the agent-facing draft
> `future-plans/migrate-3.0.md`. Prerequisite: the 2.4.0 security update
> (`future-plans/2.4.0-security-update.md`) merged into `version/3.0`.

