# Migrating to MagratheaPHP2 3.0.0

**From:** `2.3.*` or `2.4.*` · **To:** `3.0.0`

> **Draft** (2026-10-23), written with `future-plans/plan-3.0.md`. Each section is completed as its
> plan item is implemented. Plan item 31 moves it to `docs/mds/migrations/migrate-3.0.md`, which
> ships in `vendor/`. Section numbers (**M1**, **M2**, …) match plan-3.0's items.

## For the agent applying this migration

This file is self-contained: it's written to be handed to an AI agent (or a person) upgrading a
project, without the rest of the docs.

- **Work on a branch** of the project and on a **staging database**, never on production directly.
- Go through the sections **in order**.
- For each section:
  1. run its **Applies if** check;
  2. if it applies, do **What to do**;
  3. record in your report what you changed, or why it didn't apply.
- **Stop and ask the human** at every **⚠** step. These are changes that can silently alter data,
  API output or production schemas. Don't decide them alone.
- Commands are POSIX shell, run from the project root. Every `grep` excludes `vendor/`. Removed
  methods only fail **when the line runs**, so grep **all** PHP: views, cron jobs, CLI scripts,
  tests, not just classes.
- When something in the project doesn't match what this file describes, report it instead of
  guessing.

## Summary

| # | Change | Breaking? | Action | ⚠ |
|---|--------|-----------|--------|---|
| M1 | Code generator and `magrathea_objects.conf` removed | Only for code that calls the generator | Delete the conf, strip the "generated" headers | |
| M2 | File Editor removed | Yes, if `LoadFileEditor()` is called | Remove the call | |
| M3 | Deprecated/dead methods removed (`ShowAll`, `LoadVendor`, `Clean`, `CloseConnectionThanks`, …) | Yes, where called | Replace each call | |
| M4 | `MagratheaModel` has a default constructor, `$autoload`, `GetControl()` | Rarely: a constructor-less model called with an argument now loads that row | Check those calls | |
| M5 | Unknown `$dbValues` types throw | Yes, for models with invalid types (`"bool"`, …) | Fix the types | |
| M6 | Controls can declare `$model = X::class` | No | None | |
| M7 | `$strictTypes` defaults to `true` | **Yes**: int/bool/float fields become native types in PHP and in JSON | Pin `false` on every existing model | ⚠ |
| M8 | `Insert()` sends assigned timestamps as they are; reads them back | Only where code assigns timestamps before inserting | Check assignments | |
| M9 | `Update()` never writes `created_at`/`updated_at`; an unchanged object runs no query | **Yes**: `updated_at` needs `ON UPDATE CURRENT_TIMESTAMP` | Run the schema check, ALTER the tables | ⚠ |
| M10 | New `Touch()` | No | Use it where code "touched" rows with `Save()` | ⚠ (find them) |
| M11 | Every model table must have `created_at`/`updated_at` (already required, now checked) | No | Covered by M9's check | |
| M12–15 | New `DB\Schema`, `DB\SchemaCheck`, `ProjectCheck`, `DB\Dump`; `AdminDatabase` loses `GetTables()`/`GetTableColumns()`/`DoBackup()` | Only where those three are called | Use `Schema`/`Dump` | |
| M16–21 | Admin "Database" section (explorer, query editor, dumps, checks); menu items changed | Only for custom menus using removed items | Fix the menu | |
| M22–23 | Setup wizard rebuilt: dedicated entry file, token lock | Yes, for projects with a 2.x wizard entry file | Replace or delete that file | ⚠ (servers) |
| M24–26 | New `vendor/bin/magrathea` CLI | No | Optional `extra.magrathea.bootstrap` | |
| M27–31 | `skills.MD` for 3.0; `instructions.MD` replaced by `AGENTS.md` (not shipped); `docs/mds` now ships | Only for agent configs pointing at `instructions.MD` | Update those pointers | |

---

## 0. Preflight

1. **Installed version:**
   ```bash
   cat vendor/platypustechnology/magratheaphp2/src/version; echo
   grep -n "platypustechnology/magratheaphp2" composer.json
   ```
   - `2.3.*` → section 1 first.
   - `2.4.*` → skip to section 2.
   - Anything older → stop and ask the human.
2. **The git tree is clean**, on a new branch:
   ```bash
   git status --porcelain
   git checkout -b upgrade/magrathea-3.0
   ```
3. **⚠ Ask the human for:**
   - a **database backup** (or confirmation that one exists);
   - a **staging database** to run against.

   Don't run anything in this file against production until the human says so.
4. **Record API snapshots**, to compare after the upgrade:
   - for the main GET endpoints, and at least one per model returned by the API, save the JSON
     responses from the current version into `upgrade-snapshots/before/`, outside the web root;
   - ask the human which endpoints matter if you can't tell.

   M7 and M8 are mostly caught by diffing these.
5. **Run the project's test suite** and record the result. It's your baseline.

## 1. Coming from 2.3.* (skip on 2.4.*)

3.0 includes every 2.4.0 change. Apply `migrate-2.4.0.md` first: it ships in the package at
`vendor/platypustechnology/magratheaphp2/docs/mds/migrations/migrate-2.4.0.md` after section 2, or
at https://www.platypusweb.com.br/magratheaphp2/docs/?p=migrations/migrate-2.4.0 . Its sections
43–46 (the 2.4.0 security update) included.

**Skip these 2.4.0 sections when going straight to 3.0.** 3.0 supersedes them:

| 2.4.0 section | Why skip | Instead |
|---|---|---|
| 5: File Editor opt-in | the File Editor is removed | M2 |
| 6: `Query::Clean()` deprecated | removed | M3 |
| 22: timestamps unchanged, documented | timestamps change | M8–M11 |
| 37: `ShowAll()` | removed | M3 |
| 38: `LoadVendor()` | removed | M3 |
| 40: the "Strict types" select | the generator is removed | M7 |
| 41: the `mvc` generator | the generator is removed | M1 |

**⚠ 2.4.0 section 16 (real HTTP error statuses)** changes what API clients receive: errors arrive
as 4xx/5xx instead of 200. The **client** changes (frontends, mobile apps) must be **deployed before**
the upgraded backend. Ask the human how clients are released.

Projects on 2.3.0–2.3.4: also read the `### 2.3.5` … `### 2.3.1` entries in
`vendor/platypustechnology/magratheaphp2/src/changelog.md`. They're fixes with no code changes
needed; 2.3.4 is a security fix.

## 2. Composer

`^2.x`, `~2.x` and exact `2.*` constraints never receive 3.0:
```bash
composer require "platypustechnology/magratheaphp2:^3.0"
```
PHP 8.1+ (unchanged since 2.4.0). From here on, this file and `migrate-2.4.0.md` are in `vendor/`.

---

## M1. The code generator and `magrathea_objects.conf` are removed

**Applies if** the project used the admin's object editor or code generator:
```bash
find . -name "magrathea_objects.conf" -not -path "./vendor/*"
grep -rln "FILE GENERATED BY MAGRATHEA" --include='*.php' . | grep -v vendor/
grep -rn "CodeCreator\|CodeManager\|ObjectManager\|ObjectsApi\|AdminConfigApi\|GetObjectSection\|\"objects-\|\"code-gen\"" --include='*.php' . | grep -v vendor/
```

**What changed.**
- The admin no longer creates objects or generates code, and nothing reads
  `magrathea_objects.conf`.
- Generated Base classes keep working unchanged: they're plain PHP.
- The `_magrathea_config` rows `code_path`, `code_structure`, `code_namespace` are no longer read.

**What to do.**
1. **Delete `magrathea_objects.conf`.** It stays in git history.
2. **Remove the two header lines** from every generated file:
   ```
   ## FILE GENERATED BY MAGRATHEA.
   ## This file was automatically generated and changes can be overwritten through the admin
   ```
   Nothing overwrites these files anymore, and the header tells agents not to edit them.
3. **Optional, one feature at a time, and only if the human wants it:** collapse a Base class into
   its concrete class.
   - Move the properties, `MagratheaStart()` and the relation methods into `Article`.
   - Change `extends Base\ArticleBase` to `extends \Magrathea2\MagratheaModel`.
   - Delete `ArticleBase.php`.
   - Do the same for `ArticleControlBase` → `ArticleControl`, which can now just declare
     `protected static $model = Article::class;` (M6).
   - First grep for references to the Base class names, e.g. generated `GetControl()` methods
     returning `…ControlBase`, or has_many relations calling `…ControlBase::GetWhere()`.
   - Run the tests after each feature.

   **Never collapse in bulk.** The framework doesn't need it: a project can keep its Base classes
   forever.
4. Custom admin menus: remove calls to `GetObjectSection()` and `GetItem()` with the keys
   `objects-create`, `objects-conf`, `objects`, `objects-edit`, `code-gen`.
5. Optional cleanup, on the human's go-ahead:
   ```sql
   DELETE FROM `_magrathea_config` WHERE `name` IN ('code_path','code_structure','code_namespace');
   ```

## M2. The File Editor is removed

**Applies if:**
```bash
grep -rn "LoadFileEditor\|AdminFeatureFileEditor\|tools-file-editor" --include='*.php' . | grep -v vendor/
```

**What changed.** `Admin::LoadFileEditor()` and the `FileEditor` feature don't exist. Calling
`$this->LoadFileEditor()` is a fatal error when the admin loads.

**What to do.** Remove the call and any menu item for it.

## M3. Deprecated and dead methods are removed

**Applies if** any of these greps finds something:
```bash
grep -rn "ShowAll()\|LoadVendor\|Query::Clean\|::Clean(\|CloseConnectionThanks\|IncludeAllModels\|GetNextID\|GetAutoLoad\|ResponseUserPayload" --include='*.php' . | grep -v vendor/
grep -rn -- "->Start()" --include='*.php' . | grep -v vendor/   # keep only calls on a MagratheaApi object
grep -rn "AdminDatabase::Instance()->\(GetTables\|GetTableColumns\|DoBackup\|GetCommand\)" --include='*.php' . | grep -v vendor/
grep -rn "StartDB()" --include='*.php' . | grep -v vendor/
```

**What to do.**

| Removed | Replace with |
|---|---|
| `SomeControl::ShowAll()` | nothing: it was a debug helper that echoed HTML. Delete the call |
| `MagratheaPHP::LoadVendor()` | `require __DIR__."/../vendor/autoload.php";` (the call never did anything) |
| `Query::Clean($x)` | `Database::Instance()->Escape($x)` |
| `$db->CloseConnectionThanks()` | delete the call (a no-op since 2.4.0). To really close: `Disconnect()` |
| `MagratheaModel::IncludeAllModels()` | delete it; register the folder with `AddRootCodeFolder()`/`AddFeature()` |
| `$model->GetNextID()` | the id `Insert()` returns. Reading the next id beforehand is a race |
| `GetAutoLoad()` | `$this->autoload` (M4) |
| `MagratheaApiAuth::ResponseUserPayload()` | build the payload in your auth class |
| `MagratheaApi::Start()` | delete it; `Run()` does the work |
| `AdminDatabase::GetTables()` / `GetTableColumns()` | `(new \Magrathea2\DB\Schema())->Tables()` / `->Columns($table)` |
| `AdminDatabase::DoBackup()` / `GetCommand()` | `(new \Magrathea2\DB\Dump())->Export($file)` |
| `StartDB()` | `Connect()`. `StartDB()` still works in 3.0 but is deprecated |

## M4. `MagratheaModel` defaults

**What changed.** `MagratheaModel` now provides:
- a constructor `__construct($id = 0)` that runs `MagratheaStart()` and loads `$id`;
- a no-op `MagratheaStart()`;
- `protected $autoload = null`;
- a default `GetControl()` returning `<model class>Control`.

Existing constructors and `GetControl()` methods override these, as before.

**Applies if** a model class has **no** constructor of its own (and neither does its parent) and
code instantiates it **with an argument**. That argument used to be ignored; now that row is
loaded.
```bash
# model classes; check which ones declare no __construct (themselves or through their parent)
grep -rln "extends MagratheaModel\|extends \\\\Magrathea2\\\\MagratheaModel" --include='*.php' . | grep -v vendor/
```
For each such class without a constructor, search for `new ClassName(` calls with an argument.

**What to do.** Usually nothing. If such a call exists, check that loading the row is intended.

## M5. Unknown field types throw

**Applies if** a model declares a type outside `int`, `integer`, `boolean`, `string`, `text`,
`float`, `double`, `decimal`, `datetime`, `date`, `uuid`:
```bash
# style 1: $this->dbValues["x"] = "type";
grep -rhoE 'dbValues\["[A-Za-z0-9_]+"\] *= *"[A-Za-z]+"' --include='*.php' --exclude-dir=vendor . | sed -E 's/.*= *"([A-Za-z]+)"/\1/' | sort | uniq -c
# style 2: protected $dbValues = [ "x" => "type", ... ];  (only the lines inside the array)
grep -rlE 'dbValues *= *(\[|array\()' --include='*.php' --exclude-dir=vendor . | xargs -r awk '/dbValues *= *(\[|array\()/,/(\]|\));/' | grep -oE '=> *"[A-Za-z]+"' | sort | uniq -c
```

**What changed.** An unknown type used to be bound as a string silently. Now the model's first
write throws `MagratheaModelException("Unknown field type …")`.

**What to do.** Fix each unknown type by its column (check with
`vendor/bin/magrathea db:describe <table>`):

| Found | Use |
|---|---|
| `bool` | `boolean` |
| `varchar`, `char`, `enum` | `string` |
| `tinyint` | `boolean` for `tinyint(1)` flags, else `int` |
| `json`, `longtext` | `text` |
| `timestamp` | `datetime` |
| `money`, `numeric` | `decimal` |

## M6. Controls can declare `$model`

No impact. New Controls can declare `protected static $model = Article::class;` instead of
`$modelNamespace` + `$modelName` + `$dbTable`. `$dbTable` then comes from the model.

## M7. ⚠ `$strictTypes` defaults to `true`

**Applies if** any model doesn't declare `$strictTypes`, which is almost every project:
```bash
grep -rln "extends MagratheaModel\|extends \\\\Magrathea2\\\\MagratheaModel" --include='*.php' . | grep -v vendor/
grep -rn "strictTypes" --include='*.php' . | grep -v vendor/
```

**What changed.** Values loaded from the DB are cast to their declared type:
- `int` fields become PHP ints;
- `boolean` fields become `true`/`false`;
- `float` fields become floats;
- `decimal` fields stay exact strings.

That changes **API JSON** (`"42"` → `42`, `"1"` → `true`) and every `===` comparison against
strings.

**What to do.**
1. For **every** class that extends `MagratheaModel` directly (generated `…Base` classes included),
   if neither it nor its subclasses declare `$strictTypes`, add
   `protected $strictTypes = false;` to it. Behavior and JSON stay exactly as in 2.x.
2. **Check that none was missed.** For each model class, its own or an inherited declaration must
   exist. A missed model silently changes its API output.
3. **⚠ Ask the human** before removing the pin (opting into strict types) on any model. Its API
   consumers have to accept native types first.
4. New models don't declare it (strict by default).

## M8. `Insert()` sends assigned timestamps as they are and reads them back

**Applies if** code assigns `created_at`/`updated_at` before inserting:
```bash
grep -rn "created_at *=\|updated_at *=\|->created_at\b.*=\|->updated_at\b.*=" --include='*.php' . | grep -v vendor/
```

**What changed.**
- An assigned timestamp is now stored as assigned (2.x silently stored `now()` instead). That fixes
  imports through `InsertWithPk()`.
- After `Insert()`, the object's `created_at`/`updated_at` hold the **database's** values (one extra
  `SELECT`), not PHP's `now()`.

**What to do.** Check each assignment found before an `Insert()`/`Save()`. If it set the value only
because "the framework needs it", delete it and let the DB default fill it.

## M9. ⚠ `Update()` never writes timestamps; `updated_at` needs `ON UPDATE CURRENT_TIMESTAMP`

**Applies if:** always.

**What changed.**
- `Update()`/`Save()` never write `created_at` or `updated_at`; MySQL sets `updated_at` through
  `ON UPDATE CURRENT_TIMESTAMP`.
- A loaded object with no changes runs **no query** at all.
- After an update, the object reads its timestamps back.
- **A table whose `updated_at` has no `ON UPDATE CURRENT_TIMESTAMP` (and no trigger) silently stops
  updating it.**

**What to do.**
1. On **staging**, after the code changes, run the schema check:
   ```bash
   vendor/bin/magrathea check --json
   vendor/bin/magrathea models:verify --json
   ```
   Every table with a missing `DEFAULT CURRENT_TIMESTAMP` (`created_at`) or a missing
   `ON UPDATE CURRENT_TIMESTAMP` (`updated_at`) is listed with the `ALTER TABLE … MODIFY …` that
   fixes it. The ALTER keeps the column's type and nullability.
2. Apply the ALTERs on **staging**, then rerun the check until it's clean.
3. **⚠ Production:** show the human the list of ALTERs and the table sizes:
   ```sql
   SELECT table_name, table_rows FROM information_schema.tables
   WHERE table_schema = DATABASE() ORDER BY table_rows DESC;
   ```
   Changing a column definition can rebuild a large table and block writes while it runs. The human
   decides when, after a backup. Existing `NULL` `updated_at` values stay `NULL` until the next
   update. If they want them filled:
   ```sql
   UPDATE t SET updated_at = created_at WHERE updated_at IS NULL;
   ```
4. `TIMESTAMP` columns don't need converting to `DATETIME`: both support the defaults.

## M10. `Touch()`

**Applies if** code relied on `Save()`/`Update()` bumping `updated_at` with nothing else changed:
"last seen", "touch the parent", sorting by recent activity. This can't be grepped reliably.
```bash
grep -rn "updated_at" --include='*.php' . | grep -v vendor/
grep -rn "ORDER BY updated_at\|Order(\"updated_at" --include='*.php' . | grep -v vendor/
```

**What to do.**
- Where code saved an object only to bump its timestamp, call `$obj->Touch()` instead
  (`UPDATE … SET updated_at = CURRENT_TIMESTAMP`, DB clock).
- **⚠ Ask the human** about features that depend on `updated_at` freshness (activity feeds, sync
  jobs, caches keyed on `updated_at`).

## M11. Every model table needs `created_at` and `updated_at`

No new requirement: since 2.x every SELECT built from a model includes both, and Controls sort by
`created_at`. 3.0 documents it and checks it, as part of M9's check. A model table missing them is
reported as an ERROR.

## M12–M15. New services; `AdminDatabase` slimmed down

**Applies if** M3's `AdminDatabase` grep found something.

**What changed.**
- New classes: `Magrathea2\DB\Schema` (introspection), `DB\SchemaCheck` (model ↔ table drift,
  timestamps), `ProjectCheck` (config, folders, timezone, DB), `DB\Dump` (export/import).
- `AdminDatabase` keeps only the backup folder and the admin SQL helpers.
- `Database::QueryMulti()` (and `ImportFile()`) now split SQL correctly: they respect quotes,
  comments and `DELIMITER`, instead of splitting on `";\n"`.

**What to do.** Replace the calls (M3's table).

## M16–M21. The admin "Database" section

**Applies if** the project's admin builds its own menu:
```bash
grep -rn "GetDatabaseSection\|GetItem(\"\(backups\|mag-query\|query\|connect\|structure\)\")" --include='*.php' . | grep -v vendor/
```

**What changed.**
- The Database menu section has: Explorer, Queries, Query editor, Dumps (it replaces Backups),
  Checks (it absorbs Structure), and Connection.
- The Version page's "Server Time" card is now a link to the Server page's timezone check.

**What to do.** Fix custom menus that used removed item keys. `GetItem()` returns `null` for them,
and `Add(null)` adds nothing.

## M22–M23. ⚠ The Setup Wizard

**Applies if** the project has a 2.x wizard entry file:
```bash
grep -rln "Bootstrap\\\\Start\|Bootstrap\\\\\\\\Start\|PreLoad(" --include='*.php' . | grep -v vendor/
grep -rn "bootstrap_unlocked" configs/ 2>/dev/null
```

**What changed.**
- The wizard (`Magrathea2\Bootstrap\Start`) has its own entry file and works with no config at all.
- Every visit needs an **8-digit token** (`xxxx-xxxx`) that the wizard writes to
  `configs/.setup-token`. Only someone with file access can read it, and 10 wrong tries lock the
  wizard until `configs/.setup-locked` is deleted.
- With a config present, it also needs `bootstrap_unlocked = true` in the active environment, as
  in 2.4.0.
- `PreLoad()` is gone. It used to copy the sample config, which arrived unlocked.

**What to do.**
1. Replace the old entry file's content with:
   ```php
   <?php
   require __DIR__."/../vendor/autoload.php";
   Magrathea2\Bootstrap\Start::Instance()->AppPath(__DIR__."/../app")->Load();
   ```
   Adjust the paths to the project's layout. If the project doesn't need the wizard, delete the
   file.
2. **⚠ Servers:** `configs/` must not be reachable from the web: it holds `magrathea.conf` and the
   token. `vendor/bin/magrathea check` warns when it's under the document root. Ask the human to
   confirm the web root.
3. Remove `bootstrap_unlocked` from every non-`dev` section.

## M24–M26. The CLI

**What changed.** New `vendor/bin/magrathea`:
- `check`, `db:tables`, `db:describe`, `db:query`, `db:dump`, `db:import`, `models:verify`,
  `admin:reset-password`, `version`;
- `--json` for agents;
- write commands need `--write`/`--yes`.

**What to do.** If the project's bootstrap isn't `app/_inc.php`, declare it:
```json
"extra": { "magrathea": { "bootstrap": "src/bootstrap.php" } }
```
The file must not print anything.

## M27–M31. AI docs and packaging

**Applies if** the project's agent instructions (`AGENTS.md`, `CLAUDE.md`, editor rules) point at
`vendor/platypustechnology/magratheaphp2/instructions.MD`:
```bash
grep -rn "instructions.MD" --include='*.md' --include='*.MD' . | grep -v vendor/
```

**What changed.**
- `instructions.MD` no longer exists.
- The guide for writing code with the framework is
  `vendor/platypustechnology/magratheaphp2/skills.MD`, rewritten for 3.0.
- The narrative docs now ship in `vendor/platypustechnology/magratheaphp2/docs/mds/`.

**What to do.** Point the project's agent instructions at `skills.MD` and `docs/mds/index.md`. If
the project has no `AGENTS.md`, `skills.MD` §17 has a 5-line stub.

---

## Changes with no impact

- New classes and the CLI: M6, M12–M15, M24–M26.
- The admin's new Database pages: M16–M21, unless the project builds its own menu.

## Verify after upgrading

- [ ] The project's test suite passes (compare with the preflight baseline).
- [ ] `vendor/bin/magrathea check` reports no ERROR.
- [ ] `vendor/bin/magrathea models:verify` reports no ERROR.
- [ ] Diff the API snapshots (`upgrade-snapshots/before/` vs the same calls now). Expected
      differences: none if M7's pins are in place. Report every difference to the human.
- [ ] Create a record through the API, then update it: `created_at` stays, and `updated_at`
      changes (M9's `ON UPDATE` works).
- [ ] Admin: log in, open a CRUD list and an edit form, and the Database section.
- [ ] The health check endpoint answers.
- [ ] Logs show no `Unknown field type`, `Call to undefined method` or `Class … not found`.
- [ ] No `FILE GENERATED BY MAGRATHEA` header and no `magrathea_objects.conf` remain (M1).
- [ ] No `bootstrap_unlocked` outside `[dev]`, and `configs/` isn't web-reachable (M22).
