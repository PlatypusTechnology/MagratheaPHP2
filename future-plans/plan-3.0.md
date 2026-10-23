# Plan: MagratheaPHP2 3.0

Target version: **3.0.0**. Base: branch `version/3.0`, `src/version` = `2.4.0`.
Line numbers refer to the source as of commit `35c1539` (`version/2.4.0` + the 3.0 blueprint).
Files changed by the 2.4.0 security update (`AdminMenu.php`, `AdminDatabase.php`, the backups and
logs views, `views/elements/*`) will have shifted after that merge.

- Blueprint: `future-plans/magrathea-3.0.md`. Its *Settled (2026-10-23)* notes record the
  scope-level decisions.
- Decisions: the table below, with every decision and who took it.
- Agent-facing migration draft: `future-plans/migrate-3.0.md` (finalized in item 31).

---

## Prerequisite

The 2.4.0 security update (`future-plans/2.4.0-security-update.md`, items 5f–5i) is applied on
`version/2.4.0` and merged into `version/3.0`. Several 3.0 items build on it:
- 5g's process runner and file-name validation are reused by the dumps (15);
- 5h's folder-restricted reader is reused by the Dumps page (19);
- 5i's escaping of `Table()` and the form elements is relied on by every new admin page.

**Don't start 3.0 before that merge.**

## Goal

In 3.0, agents write the application code; the framework stops generating it. Four things follow:
1. **Nothing generates or edits code.** The Code Creator, `magrathea_objects.conf` and the File
   Editor go (Group A).
2. **The contract the generator used to hide becomes explicit and safe to hand-write.** Base-class
   defaults, one list of types, timestamps the database owns (Groups B, C).
3. **The project's state is readable as data.** Schema, model ↔ table drift, checks and dumps are
   framework classes. Humans use them through the admin's new Database section and the Setup
   Wizard; agents use them through a CLI (Groups D–G).
4. **The AI-facing docs are exact and reach the agent.** `skills.MD` is rewritten for 3.0,
   `AGENTS.md` replaces `instructions.MD`, and the docs and migration guides ship in `vendor/`
   (Group H).

## Decisions (2026-10-23)

**By:** "Paulo" means Paulo chose it. "Blueprint" means it's written in `magrathea-3.0.md`.
"Recommended" means it was proposed during the 2026-10-23 study and is open to override on review.

| Topic | Decision | By | Why | Items |
|---|---|---|---|---|
| Security holes found in the study | Fixed in 2.4.0 (`2.4.0-security-update.md`), not in 3.0 | Paulo | 2.4.0 isn't released, and 2.3.4 has them too | Prerequisite |
| Code generator and File Editor | Removed | Blueprint | Agents write the code | 1, 2 |
| Models | One class per model and per control for new code. Existing Base-split code keeps working | Paulo | The Base layer only protected code from the generator, which is gone. Details in item 4 | 4 |
| Agent access | Services (arrays) + CLI (`vendor/bin/magrathea`) | Paulo | Agents work in a shell, not a browser | 12–15, 24–26 |
| `$strictTypes` | Default `true`; the migration pins `false` on existing models ⚠ | Paulo | A major release is the moment to change it; agent code expects native types | 7 |
| Setup Wizard entry | Dedicated entry file (`public/setup.php`); `MagratheaPHP::Load()` unchanged | Paulo | | 22 |
| Wizard lock | 8-digit token `xxxx-xxxx` in `configs/.setup-token` (Paulo); 10 failed tries lock it (recommended) | Paulo, recommended | Only someone with file access can read it, and 10⁸ tokens is too few without a lockout | 22 |
| Dumps | `mysqldump`/`mysql` through `proc_open()` + a streaming PHP fallback | Paulo | Works on hosts with and without exec | 15 |
| AI docs | `instructions.MD` → `AGENTS.md` (not shipped); `skills.MD` is the only AI file consumers get, rewritten for 3.0 | Paulo | `instructions.MD` is stale and ships today | 27, 28 |
| Timestamps on writes | `Insert()` leaves unset ones to the DB; `Update()` never writes `updated_at`/`created_at`; the object doesn't invent values | Blueprint | One clock: the database's | 8, 9 |
| Timestamps: the open points | Read back after every write; `Touch()`; nothing changed → no query; assigned values sent as is on `Insert()` (imports); no PHP-clock opt-out flag; `DATETIME` in the framework's SQL, existing `TIMESTAMP` columns not converted | Recommended | The blueprint's "things to settle" | 8–11 |
| Table contract | Every model table has `created_at`/`updated_at`, documented and checked | Recommended | `GetProperties()` and the Controls already require it | 11, 13 |
| Model contract | `MagratheaModel` gets a constructor, a no-op `MagratheaStart()`, `$autoload` and a default `GetControl()`; one `TYPES` list where unknown types throw; Controls can declare `$model` | Recommended | What the generator used to write into every Base class | 4–6 |
| `iMagratheaModel` | Kept, not implemented by `MagratheaModel` | Recommended | It would make every subclass constructor follow `__construct($id)` | 4 |
| Removed APIs | `ShowAll()`, `LoadVendor()` (blueprint); `Query::Clean()`, `MagratheaApi::Start()`, `ResponseUserPayload()`, `CloseConnectionThanks()`, `IncludeAllModels()`, `GetNextID()`, `GetAutoLoad()` (recommended); `StartDB()` deprecated, removed in 4.0 (recommended) | Blueprint, recommended | | 3 |
| Database section | Explorer, Queries (kept), Query editor, Dumps, Checks | Blueprint (Checks: recommended) | | 16–21 |
| Query editor | A structured form that builds a real `Query` object (no `eval`) and shows its PHP chain and SQL | Recommended | One reading of the blueprint's "query editor based on Magrathea's Query service" | 18 |
| Wizard naming | Keep `Bootstrap` and `bootstrap_unlocked`; assets come from the admin pipeline, not CDNs | Recommended | No rename churn | 22, 23 |
| Wizard password reset | Moves to `magrathea admin:reset-password` | Recommended | Shell access proves ownership; a web form doesn't | 23, 26 |
| Packaging | Ship `docs/mds/**`; stop shipping `CLAUDE.md`, `AGENTS.md`, `future-plans/`, `phpunit.phar`, `scripts/`, the logos | Recommended | Agents need the guides in `vendor/`, and Paulo's personal `CLAUDE.md` shouldn't be there | 29 |

---

## Implementation instructions

1. Load the `magrathea-php2` skill before writing code, but this release rewrites it: when it
   disagrees with `src/`, `src/` wins. Follow the repo conventions: tabs, existing naming, PHPDoc
   style, `**new:**`/`**fix:**`/`**note:**`/`**removed:**` changelog lines.
2. Work **group by group, item by item, in number order**. For each item:
   1. change the code;
   2. add/adjust tests in `src/Tests/`;
   3. run the full suite (`php phpunit.phar --bootstrap src/Tests/phpUnitBootstrap.php src/Tests`).
      **Record the baseline before any change**: the 2.4.0 final count (363 tests, 1337 assertions,
      3 deprecations) plus the security update's tests;
   4. update the narrative docs in `docs/mds/` (signatures are automatic);
   5. update `skills.MD` if it covers the area, then copy it byte-for-byte to
      `/home/paulo/.claude/skills/magrathea-php2/SKILL.md` (item 27 rewrites it; until then, keep
      it consistent);
   6. add or adjust the item's section in `future-plans/migrate-3.0.md` (the draft becomes the
      shipped guide in item 31);
   7. add the item's line(s) to a new `### 3.0.0` section at the top of `src/changelog.md`;
   8. add a `docs/lib/DocMap.php` entry for each new top-level class or page, inside the existing
      categories (changing categories needs Paulo, per `docs/CLAUDE.md`);
   9. tick the item's checkbox.
3. Don't commit, push or run `scripts/release.sh`. Never `git stash` or `git checkout -- <file>`.
   The suite rewrites a tracked fixture until item 1 deletes it.
4. Environment limits: the local PHP CLI has **no `mysqli`**, no MySQL, no `mysqldump`. DB behavior
   is tested through `DatabaseSimulate`, recording `Database` subclasses (`Database::MockClass()`,
   as `modelTimestampsTest.php` does), the fake connection from `CreateConnection()`, and an
   overridable process runner (5g). The CLI is tested in-process (`Console::Run()`). Anything that
   can't be covered this way goes in the final report.
5. If the code contradicts this plan, stop and report it instead of improvising.

---

## Group A — Removals

### 1. The code generator
- [ ] done

**Error.** 3.0 doesn't generate code (blueprint, "Code Creator"). The object editor and code
generator are a whole subsystem driven by `magrathea_objects.conf`:
- classes:
  - `src/Admin/CodeCreator.php`, `CodeManager.php`, `ObjectManager.php`;
  - `src/Admin/Api/ObjectsApi.php`;
  - `src/Admin/Api/AdminConfigApi.php`. Its only method, `SaveConfig()`, returns
    `CodeManager::GetCodeCreationData()`, and its only caller is `views/javascript/code-creation.js`;
- pages: `views/pages/objects-config.php`, `objects-create.php`, `objects-edit.php`,
  `objects-view.php`, `generate-code.php`;
- actions: `views/actions/object-create.php`, `object-creation-save.php`, `object-detail.php`,
  `object-edit.php`, `object-list.php`, `object-relation-edit.php`, `object-relation-form.php`,
  `object-relations.php`, `object-remove.php`, `code-create.php`, `code-gen-view.php`, and
  `tables-list.php` (only `objects-create.php:16` includes it);
- JS: `views/javascript/object-creation.js`, `code-creation.js`;
- menu:
  - `AdminMenu::GetObjectSection()` (`AdminMenu.php:113-124`) and its items `objects-create`,
    `objects-conf`, `objects`, `objects-edit`, `code-gen` (`:282-286`);
  - `GetDatabaseSection()` also lists `objects-create` (`:104`);
  - `Admin::AddMagratheaMenu()` adds the section (`Admin.php:264`);
- Bootstrap: `src/Bootstrap/views/steps/code-generate.php` (not in the step switch: dead) and
  `db-load-magrathea-objects.php` ("Create tables from models", included by `db-run.php`);
- `Install::SetBasicConfigs()` writes `code_path`, `code_structure`, `code_namespace`
  (`Install.php:97-99`), read only by `CodeCreator::Load()` (`CodeCreator.php:37-44`);
- `magrathea_getTypesArr()` (`_Functions.php:70-73`, no callers; replaced in item 5);
- tests `codeCreatorTest.php`, `objectManagerTest.php`, and the fixture
  `test-dump/magrathea-object-test.conf`. The suite rewrites its timestamp on every run; that
  churn ends here;
- dead `use Magrathea2\Admin\ObjectManager;` lines in `views/pages/htaccess.php:4` and
  `caddy.php:4`.

**Solution.**
- **Before deleting `ObjectManager`, move its SQL → Magrathea type mapping** (`GetType()`,
  `ObjectManager.php:498-537`) into `DB\Schema::MagratheaType()` (item 12), with these fixes:
  - only `tinyint(1)` is `boolean`; other `tinyint`s are `int`;
  - `smallint`/`mediumint` → `int`;
  - `char(36)` → `uuid` (as a suggestion);
  - `json`, `mediumtext`, `longtext` → `text`;
  - `enum`/`set` → `string`;
  - `timestamp` → `datetime`.
- Delete everything listed above. Remove the `code_*` keys from `SetBasicConfigs()`.
- Grep `src/`, `docs/`, `skills.MD`, `docs/data/examples.php` for `CodeCreator|CodeManager|ObjectManager|magrathea_objects|objects-|code-gen|GetObjectSection`
  and clean every hit.
- Docs:
  - `admin/admin.md` loses its "Objects and the code generator" and "Code structure" subsections
    (2.4.0 items 27/27b);
  - `database/orm-model.md:138` and `core/config.md:229` lose their mentions.

**Impact on a 2.3/2.4 project.**
- Generated files keep working: they're plain PHP.
- `magrathea_objects.conf` is no longer read.
- The "FILE GENERATED BY MAGRATHEA … can be overwritten through the admin" headers are now false.
  They also tell agents not to edit the file, so the migration removes them.
- The `_magrathea_config` rows `code_path`, `code_structure`, `code_namespace` are dead.
- Calls to the removed classes, `GetObjectSection()`, or `GetItem()` with the removed keys break.
  `GetItem()` returns `null`, which `AdminMenu::Add()` ignores since 2.4.0 5f.

### 2. The File Editor
- [ ] done

**Error.** 3.0 doesn't edit files from the browser (blueprint). 2.4.0 already made it opt-in (item
5). It consists of:
- `src/Admin/Features/FileEditor/` (`AdminFeatureFileEditor.php`, `editor.php`, `index.php`,
  `scripts.js`, `styles.css`);
- `Admin::LoadFileEditor()` (`Admin.php:164-171`);
- the "Tools" block in `AddMagratheaMenu()` (`:282-285`);
- `AdminMenu` item `tools-file-editor` (`AdminMenu.php:290`).

**Solution.**
- Delete them.
- Tests that load the feature (`adminFeaturesTest.php`, `adminMenuTest.php`) are adjusted.
- Docs: `admin/admin.md`, `admin/admin-features.md`, `skills.MD` §13 (`:1043`) and §18 (`:1319`).

**Impact.** An `Admin` subclass calling `$this->LoadFileEditor()` hits a fatal "Call to undefined
method" **at runtime**, when the admin loads. The migration greps it.

### 3. Deprecated and dead APIs
- [ ] done

**Error.** The blueprint removes `ShowAll()` and `LoadVendor()` and asks for other deprecated
methods. The search found:

| Symbol | Where | Status today | 3.0 |
|---|---|---|---|
| `MagratheaModelControl::ShowAll()` | `MagratheaModelControl.php:310-333` | `@deprecated 2.4.0` | removed (+ `modelControlShowAllTest.php`) |
| `MagratheaPHP::LoadVendor()` | `MagratheaPHP.php:245-260` | `@deprecated 2.4.0` | removed |
| `Query::Clean()` | `DB/Query.php:92-101` | `@deprecated 2.4.0` | removed (`sqlInjectionTest.php:76-78` tests `Escape()` instead) |
| `MagratheaApi::Start()` | `MagratheaApi.php:86-100` | `@deprecated`, no callers | removed |
| `MagratheaApiAuth::ResponseUserPayload()` | `MagratheaApiAuth.php:55-72` | `@deprecated`, no callers | removed |
| `Database::CloseConnectionThanks()` | `DB/Database.php:250-256`, `DatabaseSimulate.php:145` | no-op since 2.4.0 | removed |
| `MagratheaModel::IncludeAllModels()` | `MagratheaModel.php:593-609` | legacy: includes `<site_path>/Models/*.php` | removed |
| `MagratheaModel::GetNextID()` | `MagratheaModel.php:271-279` | `SHOW TABLE STATUS`: racy, and MySQL 8 may return cached statistics | removed: use the id `Insert()` returns |
| `MagratheaModel::GetAutoLoad()`, `$autoLoad` | `MagratheaModel.php:38,117-123` | always `null` (see item 4) | removed |
| `MagratheaPHP::StartDB()` | `MagratheaPHP.php:194-200` | alias of `Connect()` | `@deprecated 3.0`, removed in 4.0 |

**Solution.**
- Delete the "removed" rows and fix their internal callers:
  - `CloseConnectionThanks()` in `MagratheaApi::HealthCheck()` (`:771`),
    `Admin/views/actions/database-test.php:14`, `Bootstrap/views/actions/database-test.php:14`,
    and `DatabaseSimulate`'s own calls (`:253-388`);
  - `StartDB()` in `Admin/Install.php:107` and in the text of
    `Bootstrap/views/steps/db-query.php:17` (rewritten in item 23 anyway).
- Mark `StartDB()` with `@deprecated 3.0` (a `Debugger` notice in DEV is enough).

**Impact.** Each removed method is a fatal error where it's still called, and only when that line
runs. The migration greps views, cron and CLI scripts as well, not just classes.

---

## Group B — The model contract for code written by agents

### 4. `MagratheaModel` defaults
- [ ] done

**Error.** The generator wrote this boilerplate into every Base class. A hand-written model has to
repeat it, or it breaks in ways that don't explain themselves:
- **`$autoload`**: `MagratheaModel` declares `$autoLoad` (`:38`), but `GetById()` reads
  `$this->autoload` (`:246`). A model without its own `protected $autoload` goes through `__get()`
  → `Get()` → "Property autoload does not exists" (`:471-491`) on `new Model($id)`. Every
  generated, framework (`AdminUser.php:27`) and test model declares it by hand.
- **The constructor**: `__construct($id = 0) { $this->MagratheaStart(); … $this->GetById($id); }`
  (`CodeCreator.php:234-243`). Without it, `new Model(5)` silently loads nothing.
- **`GetControl()`**: there's no default. `AdminCrudObject::GetField()`
  (`Features/CrudObject/AdminCrudObject.php:158-161`) calls it for every belongs_to field, so a
  model without it is a fatal error in the CRUD form.

**Why one class.** It's the convention for new code, and the "convince me" case:
- the Base layer existed only so regenerating it wouldn't overwrite hand-written code, and 3.0
  deletes the generator;
- it caused bugs that existed only because there were two control classes:
  - the Base `GetControl()` returns the **ControlBase**, so the CRUD skipped the app's
    `<Model>Control` overrides (2.4.0 item 27b);
  - generated has_many relations call `ControlBase::GetWhere()` (`CodeCreator.php:184`);
- it costs 4 files, 2 namespaces and 2 autoload folders per table (`AddFeature()`,
  `MagratheaPHP.php:106-115`), and the autoloader probes every folder for every class;
- `AdminUser`, `AppConfig` and `AdminLog` have been single classes since 2023.

Inheritance from `MagratheaModel`/`MagratheaModelControl` stays; only the generated middle layer
goes.

**Solution.**
- `public function __construct($id = 0)` in `MagratheaModel`: `$this->MagratheaStart();` then, if
  `!empty($id)`, set the PK and `GetById($id)`, the same as the generated constructor. Subclass
  constructors still override it: generated Base classes, framework models, and the app's own
  `parent::__construct($id)` chains are unchanged.
- `public function MagratheaStart() {}`: a no-op. Models set `$dbTable`/`$dbPk`/`$dbValues` there,
  or as property defaults.
- `protected $autoload = null;` replaces `$autoLoad`. `GetAutoLoad()` goes (item 3).
- `public function GetControl()`: `static::class."Control"`, the same convention as
  `AdminCrudObject::SetObject()`. If that class doesn't exist, it throws `MagratheaModelException`
  naming it and saying "declare GetControl()". Generated Base classes override it, as before.
- **`MagratheaModel` does not implement `iMagratheaModel`.** An interface that declares
  `__construct($id)` makes every subclass constructor follow that signature, so a hand-written
  `__construct()` or `__construct(int $id)` would be a fatal error at class load. Nothing in `src/`
  checks `instanceof iMagratheaModel`; the interface stays for the classes that already implement
  it.
- Framework models: `AdminUser`, `AppConfig` and `AdminLog` rename `Start()` to `MagratheaStart()`
  and drop their constructors (`AdminUser.php:29-48`, `AppConfig.php:29-56`, `AdminLog.php:29-38`).
- Tests (new `modelDefaultsTest.php`):
  - a model with only properties and `MagratheaStart()` (no constructor, no `$autoload`, no
    `GetControl()`) loads by id through a recording DB;
  - `GetControl()` resolves `<FQCN>Control`;
  - a missing control class throws, with its name in the message;
  - the legacy shape (own constructor, `$autoload`, own `GetControl()`) behaves as before;
  - framework models still load.

**Impact.**
- A model class with **no** constructor that was instantiated with an argument (`new X($v)`) now
  loads row `$v`; before, the argument was ignored.
- A model without its own `GetControl()` now gets `<FQCN>Control`, or an exception instead of
  "undefined method".
- Nothing else changes.

### 5. Field types: one list, unknown types throw
- [ ] done

**Error.**
- `GetDataTypeFromField()` (`MagratheaModel.php:553-573`) has no `default`, so an unknown type
  returns `null` and `PrepareAndExecute()` binds it as a string (`DB/Database.php:794-812`).
- The framework's own `AdminUser` declares `"active" => "bool"` (`AdminUser.php:47`), which isn't
  a type. It works only because MySQL converts the string.
- There are two type lists, and both are incomplete: `magrathea_getTypesArr()` has no `uuid`, and
  `ObjectManager::GetTypesArr()` is deleted in item 1.
- Without the generator's select, agents type these strings by hand.

**Solution.**
- `const TYPES = ["int", "boolean", "string", "text", "float", "decimal", "datetime", "date", "uuid"];`
  on `MagratheaModel`. The aliases `integer` and `double` stay accepted: `GetDataTypeFromField()`
  and `CoerceValue()` already handle them.
- `GetDataTypeFromField()`'s `default` throws `MagratheaModelException("Unknown field type [x]:
  use one of …")`. Writes (`Insert()`/`Update()`/`Delete()`) let it propagate, and the drift check
  (item 13) reports it with the model and field.
- `AdminUser`: `"active" => "boolean"`.
- Tests:
  - an unknown type throws on `Insert()`/`Update()`;
  - the aliases work;
  - every framework model's `$dbValues` uses only `TYPES`.

**Impact.** A model with an unknown type (`"bool"`, `"varchar"`, `"tinyint"`, `"json"`, a typo)
throws on its first write. The migration lists every type the project uses.

### 6. Controls: `$model` and a derived `$dbTable`
- [ ] done

**Error.** A Control repeats what its model already knows: `$modelNamespace`, `$modelName`,
`$dbTable` (`MagratheaModelControl.php:27-29`). The generator kept them in sync; an agent has to
remember to. A Control whose `$dbTable` differs from the model's makes `GetAll()`, `GetWhere()`
and `GetListPage()` (`:213-264`) read another table than `GetById()` does.

**Solution.**
- `protected static $model;`, a class name (`Article::class`). `GetModelName()` returns it when
  it's set, else `$modelNamespace."\\".$modelName` as today.
- New static `GetDbTable()`: `static::$dbTable` when set, else the model's own table (cached per
  `static::class`). Every internal read of `static::$dbTable` goes through it.
- Tests:
  - a Control with only `$model` queries the model's table;
  - the 3-property form is unchanged;
  - an explicit `$dbTable` wins.

**Impact.** None (additive).

### 7. `$strictTypes` defaults to `true`
- [ ] done

**Error.** With `$strictTypes = false` (`MagratheaModel.php:45`), every `int`/`boolean`/`float`
field comes back as a string. Code and tests written by agents assume native types. 2.4.0 made
`true` the recommendation for new models but kept the old default (2.4.0 item 27).

**Solution.**
- `protected $strictTypes = true;`.
- Audit the admin for string comparisons of model fields (e.g. `=== "1"`, `== "0"`) and fix them.
  The framework models become strict.
- Tests: the strict-types tests flip their default case.

**Impact ⚠.** Every model that doesn't declare `$strictTypes` returns typed values: `"42"` → `42`,
`"1"` → `true`. That changes API JSON and `===` comparisons. The migration adds
`protected $strictTypes = false;` to every existing model that doesn't declare it (in the Base
class for generated models), unless its consumers have been checked. One missed model silently
changes its JSON, so the migration also diffs API snapshots.

---

## Group C — Timestamps owned by the database

What the blueprint settles here:
- `Insert()` keeps leaving unset timestamps to the DB;
- `Update()` stops writing `updated_at`;
- the object doesn't invent values.

What this plan adds:
- after a write, the timestamps are read back;
- `Touch()`;
- nothing changed means no query;
- no opt-out flag: the schema check (item 13) lists the tables that need `ON UPDATE`;
- imports keep assigned values;
- `DATETIME` in the framework's own SQL.

### 8. `Insert()`: assigned timestamps sent as they are; DB values read back
- [ ] done

**Error.** `CreateInsertQuery()` (`MagratheaModel.php:318-355`) replaces an assigned
`created_at`/`updated_at` with PHP's `now()` (`:336-337`), so `InsertWithPk()` imports lose their
original dates. It then sets both to `now()` on the object (`:348-349`) without reading the row.
That's wrong whenever MySQL's clock, zone (`db_sync_timezone = false`) or default isn't PHP's
`now()`.

**Solution.**
- A non-null timestamp is sent as assigned. `null` ones are left to the DB, as today.
- After a successful `Insert()`/`InsertWithPk()`, one `SELECT created_at, updated_at FROM <table>
  WHERE <pk> = <id>` runs (`Query::Select()` + `Where([pk => id])`, so the value is escaped) and
  fills the object. Then `TakeSnapshot()` runs again.
- Tests: rewrite `modelTimestampsTest.php`:
  - assigned values are sent as is;
  - unset ones aren't sent;
  - the object holds the values the recording DB returns for the read-back;
  - a failed insert doesn't read back.

**Impact.**
- Code that assigned a timestamp before `Insert()` used to store `now()`; now it stores what it
  assigned.
- `$obj->created_at` right after `Insert()` is the DB's value, equal to PHP's when the zones are
  synced (2.4.0).
- Each insert costs one more query. Bulk imports should use `Query::Insert()`.

### 9. `Update()`: the DB owns `updated_at`
- [ ] done

**Error.**
- `Update()` (`:397-449`) always adds `updated_at` for a loaded object (`:405`) and writes PHP's
  `now()` (`:429-430`). So there are two clocks (inserts use MySQL's), and
  `ON UPDATE CURRENT_TIMESTAMP` and `BEFORE UPDATE` triggers are always overridden.
- An unchanged loaded object still runs an `UPDATE` that sets only `updated_at`.
- With 2.4.0's partial updates (item 16), dropping `updated_at` would leave an empty `SET`, which
  is a SQL error.

**Solution.**
- `Update()` never writes `created_at` or `updated_at`, loaded or not (`created_at` was already
  skipped, `:416`). Explicit values go through `Query::Update()`. `Set()`/`Assign()` keep ignoring
  both (`:510`).
- No field to write means no query, and `true` is returned.
- After a query, the timestamps are read back, as in item 8.
- Tests (`modelPartialUpdateTest.php`, `modelTimestampsTest.php`):
  - an unchanged loaded object runs no query;
  - a changed field writes only that field;
  - timestamps are never in the `SET`, loaded or not;
  - the read-back fills `updated_at`.

**Impact ⚠.**
- Tables whose `updated_at` has no `ON UPDATE CURRENT_TIMESTAMP` and no trigger silently stop
  updating it after the insert. Item 13's check lists them with the ALTER; the migration applies
  it.
- `Save()` on an unchanged object no longer bumps `updated_at`; use `Touch()` (item 10).
- `$obj->updated_at = now(); $obj->Save();` no longer writes the assignment.

### 10. `Touch()`
- [ ] done

**Error.** MySQL fires `ON UPDATE` only when some column value actually changes, so after item 9
nothing can bump `updated_at` on purpose ("last seen", "touch the parent").

**Solution.**
- `public function Touch(): bool` runs `UPDATE <table> SET updated_at = CURRENT_TIMESTAMP WHERE
  <pk> = ?` through `PrepareAndExecute()`, with the PK bound by its type (as `Delete()` does), then
  reads back.
- An object without a PK throws `MagratheaModelException`.
- Tests: the SQL and its binding; no PK throws; the read-back.

**Impact.** None (new).

### 11. The timestamp schema
- [ ] done

**Error.**
- Every table that a model maps needs `created_at` and `updated_at`, but nothing says so:
  - `GetProperties()` (`MagratheaModel.php:69-74`) adds both to every SELECT built from a model
    (`GetById()`, `Query::Obj()`);
  - `GetAll()`, `GetListPage()` and `GetWhere()` sort by `created_at`
    (`MagratheaModelControl.php:214,224,262`).
- After item 9, `updated_at` also needs `ON UPDATE CURRENT_TIMESTAMP`.
- `src/Admin/database.sql` mixes types: `created_at TIMESTAMP` and `updated_at DATETIME`
  (`:7-8, 14-15, 29-30, 40-41`).
- `_magrathea_users.last_login` is a bare `timestamp` (`:26`). On a server with
  `explicit_defaults_for_timestamp=OFF` (MySQL 5.7's default), the first bare TIMESTAMP column gets
  `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`, so `last_login` would change on every
  update.

**Solution.**
- `database.sql`:
  - `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`;
  - `updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP`;
  - `last_login DATETIME NULL`.

  That's for new installs. `DATETIME` has no 2038 limit and no session-zone conversion, and since
  2.4.0 `CURRENT_TIMESTAMP` is evaluated in PHP's zone. Existing `TIMESTAMP` columns aren't
  converted: both types work.
- Document the table contract (`orm-model.md`, `skills.MD`): every table a model maps has these two
  columns (`TIMESTAMP` works too).
- The check that finds tables breaking the contract is item 13. It prints an
  `ALTER TABLE … MODIFY …` that keeps the column's type and nullability, and only adds
  `DEFAULT`/`ON UPDATE`.

**Impact.** The schema requirement ⚠ is handled by the migration: run the check, then apply the
ALTERs, with backups and on staging first.

---

## Group D — The service layer (shared by the admin, the wizard and the CLI)

Every class returns plain arrays. Findings use `TimezoneCheck`'s shape and levels (`OK`/`INFO`/
`WARNING`/`ERROR`, `src/TimezoneCheck.php:29-32`).

### 12. `DB\Schema`: introspection
- [ ] done

**Error.** The admin's table helpers are thin: `AdminDatabase::GetTables()` and `GetTableColumns()`
(`AdminDatabase.php:42-58, 102-118`, with `SHOW columns FROM <unquoted table>`). The Bootstrap
`show-tables` action is a third copy. An agent can't get the schema as data.

**Solution.** New `Magrathea2\DB\Schema`. It's read-only and takes an optional `Database` (default
`Database::Instance()`):
- `Tables()`: `information_schema.TABLES` (name, engine, approximate rows, collation, comment);
- `Columns($table)`: `information_schema.COLUMNS` (name, column type, nullable, default, extra,
  key);
- `Indexes($table)`: `information_schema.STATISTICS`;
- `CreateStatement($table)`: `SHOW CREATE TABLE`, with the name checked against `Tables()` and
  backtick-quoted;
- `MagratheaType($columnType)`: the mapping moved from `ObjectManager` (item 1).

Literals are escaped with `Database::Escape()`. `AdminDatabase::GetTables()`/`GetTableColumns()`
are removed in favor of it.
- Tests: the SQL through a recording DB; the type-mapping table; an unknown table is rejected.
- Docs: new `docs/mds/database/schema.md` (DocMap: database).

**Impact.** Code calling `AdminDatabase::GetTables()`/`GetTableColumns()` moves to `Schema`.

### 13. `DB\SchemaCheck`: model discovery, drift, timestamps
- [ ] done

**Error.** The generator was the only thing that guaranteed a model matches its table. Without it,
an agent's typo in `$dbValues` surfaces as a runtime SQL error, or never (a missing column is just
not saved).

**Solution.** New `Magrathea2\DB\SchemaCheck`:
- `FindModels()`:
  - scans `MagratheaPHP::Instance()->codeFolder` for `*.php`, not recursive, like the autoloader;
  - reads `namespace` and `class` declarations with `token_get_all()`, skipping abstract classes;
  - loads candidates through the autoloader and keeps the subclasses of `MagratheaModel`;
  - instantiates each with `new $class()` (no id, so no query) to read its table, PK and
    `$dbValues`;
  - reports a failure as a finding instead of throwing.
- `CheckModel($model)`:
  - table missing: ERROR;
  - a `$dbValues` field missing in the table: ERROR (writes fail);
  - a column missing in `$dbValues`: INFO;
  - `$dbPk` isn't the primary key: WARNING;
  - an unknown type (item 5): ERROR;
  - `float` on a `DECIMAL` column: WARNING (it rounds with strict types, 2.4.0 item 26);
  - other mismatches (by `Schema::MagratheaType()`): WARNING;
  - `created_at`/`updated_at` missing from the table: ERROR (every SELECT from the model fails).
- `CheckTimestamps($table)`:
  - `created_at` without `DEFAULT CURRENT_TIMESTAMP` while `NOT NULL`: ERROR;
  - `updated_at` without `on update CURRENT_TIMESTAMP` in `EXTRA`: ERROR, with the fixing ALTER
    (item 11).
- `CheckAll()`: the above for every model, plus tables with no model (INFO).
- Tests:
  - fixture models in a temp code folder;
  - fake schema rows;
  - each finding;
  - the ALTER keeps type and nullability.
- Docs: `schema.md`.

**Impact.** None (new). The migration runs it.

### 14. `ProjectCheck`: configuration and environment
- [ ] done

**Error.** The blueprint's "structure verifications and configuration verifications" are scattered
or missing:
- the Bootstrap `Structurate()`/`ValidateStructure()` echo HTML (`Bootstrap/Start.php:141-200`);
- the admin's Structure page checks folders inline (`views/pages/structure.php:26-75`);
- `TimezoneCheck::Run()` is separate.

**Solution.** New `Magrathea2\ProjectCheck::Run(bool $withDatabase = true): array`:
- PHP ≥ 8.1, `mysqli`, and `proc_open` for dumps (INFO if missing);
- the config file is readable, and `use_environment` names an existing section;
- the active section has `db_host`, `db_name`, `db_user` and `timezone`;
- `bootstrap_unlocked` set in a non-`dev` environment: WARNING;
- `logs_path`, `cache_path`, `backups_path` exist and are writable;
- `configs/` isn't under `$_SERVER["DOCUMENT_ROOT"]` (web only);
- the DB connects, and the admin tables exist (`_magrathea_config`);
- `TimezoneCheck::Run()`'s findings;
- `SchemaCheck::CheckAll()` when `$withDatabase`.

The wizard (item 23), the admin's Checks page (item 20) and `magrathea check` (item 26) use it.
- Tests: each finding with `Config::SetConfig()` fixtures, temp folders and a fake DB.

**Impact.** None (new).

### 15. `DB\Dump`: export and import
- [ ] done

**Error.**
- Backups shell out to `mysqldump` (fixed in 2.4.0 5g), and there's no import.
- `Database::ImportFile()` (`DB/Database.php:763-770`) → `QueryMulti()` splits on `";\n"`
  (`:703`). A value containing `;\n` breaks a statement, `DELIMITER` blocks (triggers, procedures)
  break, and the whole file plus every result is held in memory.
- Hosts with exec disabled get nothing.

**Solution.** New `Magrathea2\DB\Dump`:
- `Export($file, $options)` / `Import($file, $options)`, with files in `backups_path`. Names go
  through the 5g validation, moved here from `AdminDatabase`.
- **Binary driver** (default when `proc_open()` exists and `mysqldump`/`mysql` answer `--version`):
  - uses 5g's runner and temp defaults file (also moved here);
  - export: `--single-transaction --routines --triggers --default-character-set=utf8mb4
    --result-file=…`;
  - import: `mysql <db>` with the file as stdin (a `proc_open` file descriptor).
- **PHP driver** (fallback, or `driver => "php"`):
  - export: writes a header (`SET NAMES utf8mb4`, `SET time_zone = '+00:00'`, `SET
    FOREIGN_KEY_CHECKS = 0`). Its own session uses `+00:00` too, so `TIMESTAMP` values round-trip
    exactly despite 2.4.0's session-zone sync;
  - per table: `SHOW CREATE TABLE`, then rows streamed with an unbuffered query as multi-row
    `INSERT`s (`Escape()`, `NULL`, `0x…` for binary columns);
  - views and triggers are exported; routines are reported as unsupported;
  - import: the new `DB\SqlSplitter` streams the file in chunks. It respects `'…'`, `"…"` and
    `` `…` `` with backslash escapes, `-- ` and `#` line comments and `/* */` blocks (keeping
    `/*!…*/`), `DELIMITER`, and CRLF. Each statement runs through `Query()`, nothing accumulates,
    and it stops at the first error (statement number + error) unless `continue` is set.
- `Database::QueryMulti()` (string input) uses `SqlSplitter` instead of `explode(";\n")`. The admin
  install SQL keeps working.
- `AdminDatabase::DoBackup()` is removed. The Dumps page (item 19) uses `Dump`.
- Tests:
  - splitter cases: quotes, escapes, comments, `DELIMITER`, conditional comments, CRLF, a statement
    across a chunk boundary;
  - the binary driver's command arrays (runner override);
  - PHP export of a fake table (`NULL`, quotes, binary, the header);
  - import stops at the first error.
- Docs: `schema.md` (or its own `database/dump.md`).

**Impact.** Code calling `AdminDatabase::DoBackup()` moves to `Dump::Export()`. `QueryMulti()`
splits strings correctly now, so statements that used to break at a `;\n` inside a value run whole.

---

## Group E — The admin "Database" section

All pages escape through 2.4.0 5i's `Table()`/forms. Table and column names are checked against
`Schema` before use.

### 16. Explorer
- [ ] done

**Solution.**
- **Tables page:** name, approximate rows, engine, the mapped model (from
  `SchemaCheck::FindModels()`) and its drift status.
- **Table page:**
  - columns (name, type, null, default, extra, key, suggested Magrathea type);
  - indexes;
  - `SHOW CREATE TABLE` (copyable);
  - the model's findings;
  - a read-only preview of the first 20 rows.
- Tests: page logic in a small class (e.g. `Admin\DatabaseExplorer`), covered with fake schema
  rows.

### 17. Queries
- [ ] done

**Solution.**
- `views/pages/db-query.php` and `query-run-card.php` stay (escaped since 2.4.0 5i).
- Rendering is capped at 1000 rows, with a notice.
- Non-SELECT statements ask for confirmation in the UI.
- The table list (`tables-view.php`) uses `Schema::Tables()`.

### 18. Query editor
- [ ] done

**Error.** The blueprint wants "a query editor that will create queries based on Magrathea's Query
service". 2.4.0 removed the `eval()` Query Tester (5f).

**Solution.** New page plus `Admin\QueryEditor::Build(array $spec): array`.
- The form:
  - type (select/insert/update/delete);
  - table (from `Schema`);
  - fields (checkboxes from the columns);
  - where rows: column, an operator from `=`, `!=`, `<`, `>`, `<=`, `>=`, `LIKE`, `IS NULL`,
    `IS NOT NULL`, `IN`, and a value;
  - order, limit, page;
  - values for insert/update.

  `delete` requires at least one where row.
- `Build()` makes a real `Query` through method calls:
  - `=` uses `Where([...])`;
  - the other operators use string conditions built from whitelisted column names and `Escape()`d
    values.

  It returns `["php" => "Query::Select()->Table(\"articles\")->…", "sql" => $q->SQL()]`.
- "Run" posts the spec again. SELECT renders rows; writes ask for confirmation and show the
  affected rows.
- Tests:
  - `Build()` for every type and operator;
  - unknown tables and columns are rejected;
  - the PHP chain and the SQL agree.

### 19. Dumps (replaces Backups)
- [ ] done

**Solution.**
- One page:
  - list files in `backups_path`: name, size, date;
  - **Export**: file name, optional tables; shows the driver used;
  - **Download**: streamed with `Content-Disposition`, name validated;
  - **Upload** `.sql`: saved under a validated name, never run on upload;
  - **Import**: POST + CSRF + a confirmation naming the file and the database;
  - **Delete**: POST.
- Replaces `views/pages/backups.php`, `views/actions/backups-list.php`, `backup-file.php` and
  `Admin/Api/DatabaseApi.php` (2.4.0 5g versions), using `Dump` (item 15).
- `AdminDatabase` keeps `GetBackupFolder()` and the admin SQL file helpers, nothing else.
- Tests: the upload, download and delete name checks; import and export go through a fake `Dump`.

### 20. Checks
- [ ] done

**Solution.**
- New page with `ProjectCheck::Run()` findings, grouped:
  - configuration;
  - folders;
  - database;
  - timezone;
  - schema: timestamps with their fix SQL (copy only, never run automatically), and model drift.
- It absorbs the Structure page's folder checks. `views/pages/structure.php` becomes a link, or is
  removed.

### 21. Menu and the Version page
- [ ] done

**Solution.**
- `AdminMenu::GetDatabaseSection()`: Explorer, Queries, Query editor, Dumps, Checks, Connection
  (`db-connect.php`).
- The Server page keeps its Timezone check.
- `views/pages/version.php`'s "Server Time" card (`:29-39`) is replaced by a link to the Server
  page's Timezone check (blueprint, timezone extras).
- Tests (`adminMenuTest.php`): the section's items, and no generator or File Editor items.

---

## Group F — The Setup Wizard (Bootstrap, rebuilt)

### 22. Entry, modes and the token lock
- [ ] done

**Error.**
- Today the wizard needs a config just to decide whether it's unlocked (`Start::IsUnlocked()`,
  `Bootstrap/Start.php:69-76`).
- `PreLoad()` (`:265-268`) copies the sample, which arrives as `dev` +
  `bootstrap_unlocked = true`. That's 2.4.0's accepted gap: with no config, the wizard is open to
  anyone.
- The blueprint wants a WordPress-style wizard for a new project with no `magrathea.conf`. That's
  exactly WordPress's takeover scenario on a deployed server.

**Solution.**
- **Entry file** (in the skeleton, item 27):
  ```php
  <?php
  // public/setup.php: Magrathea setup wizard (locked by a token only the server's owner can read)
  require __DIR__."/../vendor/autoload.php";
  Magrathea2\Bootstrap\Start::Instance()->AppPath(__DIR__."/../app")->Load();
  ```
  `Start::AppPath()` sets `MagratheaPHP::Instance()->AppPath()`. The wizard checks for
  `<magRoot>/configs/magrathea.conf` itself and never calls `MagratheaPHP::Load()` first, because
  that dies on a missing config (code 424041).
- **Modes:**
  - **setup**: no config file. The token is required;
  - **maintenance**: the config exists. It needs `bootstrap_unlocked = true` in the active
    environment (the 2.4.0 rule) **and** the token.

  Anything else gets the locked page (403).
- **Token** (`configs/.setup-token`):
  - **issuing**:
    - created when it's missing, on a visit that is allowed to ask for it;
    - mode `0600`;
    - line 1 is `xxxx-xxxx`, 8 digits from `random_int(0, 99999999)`, zero-padded, readable with
      `cat`;
    - line 2 counts failures, updated under `flock()`;
    - if `configs/` is missing, `mkdir` (`0750`) is tried; if it's unwritable, an instructions
      page;
  - **checking**:
    - the form accepts `xxxx-xxxx` or `xxxxxxxx` (spaces and dashes stripped);
    - the comparison uses `hash_equals()`;
  - **lockout**: at **10 failures** the file is renamed to `configs/.setup-locked`, and no token is
    accepted until the owner deletes that file. The next visit issues a new token. The page says
    exactly this;
  - **session**:
    - success runs `session_regenerate_id(true)` and stores `hash("sha256", token)` in the
      session;
    - every request compares that hash with the file, so deleting or replacing the file revokes
      the session;
  - the finish step (item 23) deletes the file.
- Wizard POSTs use `AdminCsrf` (`GetToken()`/`Validate()`). `Start::ACTIONS` (the 2.4.0 whitelist)
  stays, and every action requires a verified session.
- `PreLoad()` and `CreateDefaultConfigFile()` (`:231-252`) are removed: the config is written only
  by item 23's form, after the token.
- `src/Errors/views/error-magratheaconf.php` (`Load()`'s "config missing" page) says how to run the
  wizard. `Load()` itself is unchanged.
- Tests:
  - the token's format and `0600` mode;
  - normalization and `hash_equals`;
  - 10 failures lock it, and deleting the lock issues a new token;
  - setup needs only the token; maintenance needs the flag and the token;
  - changing the token file revokes the session;
  - an action without a verified session gets 403;
  - the web-root warning.

**Impact.**
- Projects with a 2.x wizard entry file (`Bootstrap\Start::Instance()->…->Load()`, with or without
  `PreLoad()`) replace it with the entry above, or delete it.
- 2.4.0 users of the wizard now also need the token.

### 23. Steps
- [ ] done

**Solution.** Each step is validated on the server.
1. **Requirements**: the `ProjectCheck` items that need no config (PHP, extensions, writable
   folders, `configs/` outside the web root).
2. **Configuration**: writes `configs/magrathea.conf`, following
   `src/Bootstrap/docs/magrathea.conf.sample`, through `ConfigFile::Save()`.
   - Fields:
     - environment name (`[general] use_environment`, default `dev`);
     - `db_host`, `db_port`, `db_name`, `db_user`, `db_pass`;
     - **`timezone` as a `<select>` of `timezone_identifiers_list()`**, validated against that
       list (blueprint);
     - `app_url`;
     - `logs_path`, `cache_path`, `backups_path`, defaulting under `magRoot`;
     - `jwt_key`, default `bin2hex(random_bytes(32))`;
     - `bootstrap_unlocked`, offered for `dev` only.
   - `ConfigFile::SaveValueOnConfig()` wraps values in `"…"` without escaping
     (`ConfigFile.php:128-132`), so values with `"` or a newline are rejected.
   - In maintenance mode, only the active section is edited.
3. **Database**:
   - test the connection with the entered values;
   - `TimezoneCheck::Run()` (moved here from `config-edit.php`);
   - list tables (`Schema`).
4. **Import** (optional): a dump from `backups_path` or an upload → `Dump::Import()`. Shows the
   driver, the statement count, and the first error.
5. **Admin**:
   - `Install::InstallDatabase()` when `_magrathea_config` is missing;
   - the first super admin (email + password of 12+ characters) when `CanCreateFirstUser()` allows
     it (`Admin/Start.php:170-176`).

   The admin entry file is part of the skeleton (item 27), not written here.
6. **Checks**: `ProjectCheck::Run()` with the schema's timestamp and drift findings and their fix
   SQL.
7. **Finish**:
   - deletes the token;
   - reminds the user to remove `bootstrap_unlocked` outside dev;
   - links to the admin and the docs.

**2.x pieces removed or replaced:**
- steps `structure`, `config-file`, `config-edit`, `log-and-debug`, `db-connect`, `db-run`,
  `db-query`, `install-admin`, `develop` (plus item 1's two);
- `tools.php`: its password reset becomes `magrathea admin:reset-password` (item 26). Shell access
  proves ownership; a web form doesn't;
- actions `run-query`, `user-reset`, `users-get`, `show-tables`, `install-admin`,
  `install-admin-preview`, `database-test`, `save-config`, replaced by the steps' handlers;
- the CDN assets in `views/index.php` (Bootstrap from jsdelivr, jQuery from code.jquery.com),
  replaced by the admin's compressed vendor assets.

**Tests:**
- writing the config into a temp folder;
- `"` rejected;
- the timezone outside the list rejected;
- creating the first user is guarded by `CanCreateFirstUser()`;
- step order;
- each step's handler without a verified session is refused.

---

## Group G — The CLI for agents

### 24. The `bin/magrathea` runner
- [ ] done

**Error.** Agents work in a shell. Without a CLI, everything above is reachable only through a
browser.

**Solution.**
- `bin/magrathea` (PHP with a shebang) and `"bin": ["bin/magrathea"]` in `composer.json`, which
  gives `vendor/bin/magrathea`.
- **Finding the project's bootstrap**, in order:
  1. `--bootstrap=<file>`;
  2. `composer.json` `extra.magrathea.bootstrap` (from the nearest `composer.json` above the cwd);
  3. `app/_inc.php`, the `skills.MD` convention.

  The file is `require`d, and it must not print (`skills.MD` §1).
- It refuses to run outside the CLI.
- **Output**: text, or `--json` for a single JSON document.
- **Exit codes**: 0 ok, 1 an ERROR finding or a failed command, 2 a usage error.
- A small dispatcher, `Magrathea2\Cli\Console::Run(array $argv, $out, $err): int`, so it's testable
  in-process.
- `ErrorManager::DisplayException()`/`DisplayMesage()` (`src/Errors/ErrorManager.php:26-42`) print
  plain text when `PHP_SAPI === "cli"`, so a missing config reads as text in a terminal instead of
  an HTML page.
- Tests: argument parsing, the bootstrap order, JSON output, exit codes, the non-CLI refusal.
- Docs: new `docs/mds/core/cli.md` (DocMap: core).

### 25. Writes from the CLI need explicit flags
- [ ] done

**Solution.**
- Commands that write or change data refuse to run without a flag:
  - `db:query` with a non-SELECT needs `--write`;
  - `db:import` needs `--yes`.
- There's no interactive prompt, because agents can't answer one. The flag is the consent.
- Tests: refused without the flag; runs with it.

### 26. Commands
- [ ] done

**Solution.**

| Command | Uses | Notes |
|---|---|---|
| `check [--no-db]` | `ProjectCheck::Run()` | exit 1 on any ERROR |
| `db:tables` | `Schema::Tables()` | |
| `db:describe <table>` | `Schema` + `SchemaCheck` | columns, indexes, `SHOW CREATE`, mapped model and its findings |
| `db:query "<sql>" [--write]` | `Database` | SELECT → rows; others need `--write` |
| `db:dump [file] [--tables=a,b] [--driver=binary\|php]` | `Dump::Export()` | |
| `db:import <file> --yes [--driver=…] [--continue]` | `Dump::Import()` | |
| `models:verify [Class]` | `SchemaCheck` | exit 1 on any ERROR. Agents run it after writing or changing a model |
| `admin:reset-password <email> [--password=…]` | `AdminUserControl::GetByEmail()` + `SetNewPassword()` | prints a generated password once |
| `version` | `MagratheaPHP::Version()`, `AppVersion()` | |

- Tests: each command against fakes.
- `skills.MD` gets a "Verify your work" section (item 27).

---

## Group H — AI-facing docs and packaging

### 27. `skills.MD` for 3.0
- [ ] done

**Error.** In 3.0, `skills.MD` is the code generator, so every inaccuracy is a bug in generated
code. It still teaches:
- the Base split (§3–4, `:170-261`, `:327-362`) and the generator (`:172`, `:285`);
- 2.x history notes on almost every rule ("since 2.4.0", "requires 2.2.2+");
- `catch (Exception $ex) { \Magrathea2\p_r($ex); }` in the entry points (§1 `:74-76`, §12
  `:955-957`, §17 `:1262-1264`), which prints exception details on a production page.

It also points to `instructions.MD` (`:13`).

**Solution.** Rewrite it for 3.0 only:
- **Models and controls:**
  - single-class models and controls (items 4, 6), with relations as explicit methods;
  - `MagratheaModel::TYPES`;
  - strict types by default;
  - the table contract and timestamp rules (`Touch()`, read-back, `Query::Update()` for explicit
    values);
  - `AddFeature()`'s `Base` folder mentioned only for legacy projects.
- **Removed from the cookbook:** the generator, File Editor, `LoadVendor()`, `Clean()`, `ShowAll()`
  and `mvc`.
- **New sections:**
  - "Setting up a project": the skeleton, `public/setup.php`, the token;
  - "Verify your work": `vendor/bin/magrathea check`, `models:verify`, `db:describe --json`;
  - dumps.
- **Entry points** log and answer a generic error instead of `p_r($ex)`.
- **§17 skeleton:** `public/setup.php` and an `AGENTS.md` stub (5 lines pointing to
  `vendor/platypustechnology/magratheaphp2/skills.MD` and `…/docs/mds/`, which ship after item 29).
- **"Reference files":** the shipped `docs/mds/index.md`, not `instructions.MD`.
- **§18 checklist:** rewritten.

Copy it to `SKILL.md`.

### 28. `AGENTS.md` replaces `instructions.MD`
- [ ] done

**Error.** `instructions.MD` is stale and ships in `vendor/`:
- `documentation/` and `config/` paths (`:13-14`);
- a `[section:environment]` syntax that never existed (`:123`);
- "always `Query::Clean()`" and "call `LoadVendor()` first" (`:174,177`);
- `ToJson()` for API responses (`:176`, which contradicts `skills.MD`);
- `{param}` routes (`:222`, really `:name`);
- fictional `AdminCrudObject` properties (`:145`);
- PHP 8.0+.

**Solution.**
- Delete `instructions.MD`.
- New root `AGENTS.md`, a guide for agents working **on the framework**:
  - an accurate source map;
  - how to run the suite and its limits (no `mysqli`; `DatabaseSimulate`, `MockClass()`, the fake
    connection, the runner override);
  - the conventions (tabs, PHPDoc, changelog lines);
  - the doc-sync rules (Reflector, DocMap, `skills.MD` → `SKILL.md`, from `CLAUDE.md`);
  - `future-plans/` and the per-release migration guide.
- It isn't shipped (item 29).
- Optionally, one line in `CLAUDE.md` points to it (Paulo's file: ask first).

### 29. Packaging (`.gitattributes`)
- [ ] done

**Error.** Only `/docs` is export-ignored (`.gitattributes:1`), so every consumer's `vendor/` gets:
- `CLAUDE.md`, Paulo's personal agent rules. Agents read nested `CLAUDE.md` files when they open
  files in that folder;
- `future-plans/`;
- `phpunit.phar` (5 MB);
- `scripts/`;
- the logos;
- and **not** the migration guides, which agents need there.

**Solution.**
- Replace the single rule with ignores for:
  - `/docs/*.php`, `/docs/lib`, `/docs/views`, `/docs/assets`, `/docs/data`, `/docs/cache`,
    `/docs/CLAUDE.md`;
  - `/future-plans`, `/phpunit.phar`, `/scripts`;
  - `/CLAUDE.md`, `/AGENTS.md`;
  - `/magrathea_logo.png`, `/magrathea_logo.svg`.
- What ships: `src/` (including `Tests/`, which the admin Tests page uses), `bin/`, `docs/mds/`,
  `skills.MD`, `README.md`, `LICENSE`, `composer.json`.
- Verify with `git archive HEAD | tar -t`.

**Impact.** None at runtime.

### 30. Narrative docs pass and DocMap
- [ ] done

**Solution.**
- Most pages are updated item by item. This pass checks every page that mentions removed or
  changed things:
  - `getting-started.md`, `index.md`;
  - `admin/admin.md`, `admin/admin-features.md`, `admin/admin-manager.md`;
  - `core/config.md`, `core/magrathea-php.md`, `core/global-functions.md`, `core/helper.md`,
    `core/timezone.md`;
  - `database/database.md`, `database/orm-model.md`, `database/orm-control.md`,
    `database/query-builder.md`;
  - `advanced/patterns.md`, `advanced/testing.md`;
  - `api/authentication.md`;
  - `utilities/debugger.md`, `utilities/compressors.md`;
  - `docs/data/examples.php`, `README.md`.
- New DocMap entries inside the existing categories:
  - `DB/Schema.php`, `DB/SchemaCheck.php`, `DB/Dump.php`, `DB/SqlSplitter.php` (database);
  - `ProjectCheck.php`, `Cli/Console.php` (core);
  - `Bootstrap/Start.php`, `Admin/QueryEditor.php` (admin).
- Mirror them in `index.md`.

### 31. The migration guide
- [ ] done

**Solution.**
- Finalize `future-plans/migrate-3.0.md` into `docs/mds/migrations/migrate-3.0.md`, which ships
  after item 29.
- DocMap `migrations` entry (`"migrate-3.0" => ["title" => "3.0", …]`), mirrored in `index.md`.
- The first line of the `### 3.0.0` changelog section points to it: the vendor path
  `vendor/platypustechnology/magratheaphp2/docs/mds/migrations/migrate-3.0.md`, and the docs URL.
- `skills.MD`'s "Upgrading" line does the same.
- Delete the draft from `future-plans/`.

---

## Release (after all items are done)

- [ ] `src/version` → `3.0.0` (Paulo).
- [ ] Review the `### 3.0.0` changelog section: date line, the migration `**note:**` first, the
      `**removed:**` lines complete.
- [ ] Full suite; record the counts here.
- [ ] Copy `skills.MD` → `SKILL.md` one last time and check with `cmp`.
- [ ] `git archive HEAD | tar -t` matches item 29.
- [ ] Final report:
  - files changed and the test counts;
  - what needs a real MySQL:
    - `ON UPDATE CURRENT_TIMESTAMP` behavior and the read-back values;
    - `information_schema` output on MySQL vs MariaDB;
    - real `mysqldump`/`mysql` runs;
    - a PHP-driver dump round-trip with triggers, views and binary data;
    - `TIMESTAMP` values across zones;
  - the wizard in a browser;
  - the CLI in a real project.

## Composer constraints (for the migration file)

- `^2.x` and `~2.x` constraints never receive 3.0. Projects change to `^3.0` deliberately.
- New projects require `^3.0`.

## Changelog notes

Written item by item into the `### 3.0.0` section of `src/changelog.md` (instruction 2.7).
