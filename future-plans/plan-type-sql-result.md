# Plan: typed hydration in `MagratheaModel::LoadObjectFromTableRow()`

## Problem

`MagratheaModel::LoadObjectFromTableRow()` (`src/MagratheaModel.php:127`) hydrates model
properties directly from the DB driver's row with no type coercion:

```php
public function LoadObjectFromTableRow($row){
    if(!is_array($row) && !is_object($row)) return;
    foreach($row as $field => $value){
        $field = strtolower($field);
        if(property_exists($this, $field))
            $this->$field = $value;
    }
}
```

mysqli/PDO return every column as a string regardless of its SQL type, so every model
property ends up as a string — including ints, decimals, and tinyint(1) booleans. This
propagates into JSON API responses (numeric fields serialize as quoted strings instead of
numbers, since `ToArray()`/`ToJson()` just read properties directly) and into any PHP code
doing strict comparisons (`===`/`!==`) against a model property expecting a native type.

The type metadata to fix this already exists: `$dbValues` maps every declared column to
`int` / `boolean` / `float` / `string` / `text` / `datetime` / `date` / `uuid` (used today to
drive `Insert()`/`Update()` SQL binding). `GetDataTypeFromField()` (line 440) exists but is
too coarse for this purpose — it collapses `boolean` → `integer` and `datetime`/`date` →
`date`, which is correct for SQL binding but wrong for PHP-side typing. Coercion needs its
own, finer-grained mapping.

## Constraints

- The framework is vendored via Composer into multiple independent consuming projects — a
  hydration type change alters every one of their JSON API contracts simultaneously if made
  default-on.
- Any consumer currently doing string comparisons against these fields would break in the
  other direction once fields become natively typed.
- A default-on change would be breaking and require a version bump plus coordinated retest
  across all consumers, not a patch release.

## Decisions

| Decision | Choice |
|---|---|
| Rollout | Opt-in, default off — zero behavior change until enabled |
| Mechanism | `protected $strictTypes = false;` on `MagratheaModel`, overridden to `true` per model's Base class |
| Coercion source | `GetProperties()` (not raw `$dbValues` — avoids `GetFields()`'s pk-type overwrite and its external-relation class-name entries) |
| Type map | `int` → `(int)`, `boolean` → `(bool)`, `float`/`double` → `(float)`; `string`/`text`/`uuid`/`date`/`datetime` left untouched |
| NULL handling | Stays PHP `null`, never cast (preserves unset/zero distinction) |
| Bad cast (schema drift, e.g. non-numeric string in an int column) | Throw `MagratheaModelException` naming the field and value |
| Unmapped columns (extra `SelectExtra()` columns, join aliases not in `$dbValues`) | Left untouched, exactly as today — no type to coerce to |
| Version | Next patch, `2.2.5` — matches how uuid (2.1.30) and date (2.2.2) types were released: additive, opt-in, default-off features get ordinary patch bumps in this project's convention, not semver minors |

## Performance

- `$strictTypes = false` (default): must cost nothing beyond today. Implementation branches
  once per `LoadObjectFromTableRow()` call, not once per field, so the disabled path is
  byte-for-byte the existing loop:

  ```php
  public function LoadObjectFromTableRow($row){
      if(!is_array($row) && !is_object($row)) return;
      if(!$this->strictTypes) {
          foreach($row as $field => $value){
              $field = strtolower($field);
              if(property_exists($this, $field))
                  $this->$field = $value;
          }
          return;
      }
      // new typed path
      ...
  }
  ```

- `$strictTypes = true`: adds one `GetProperties()` array build per row plus one type-map
  lookup and a native `(int)`/`(float)`/`(bool)` cast per typed column. PHP scalar casts are
  nanoseconds-to-low-tens-of-nanoseconds each; for a realistic model (10-30 columns) this is
  sub-microsecond to a few microseconds per row — negligible next to the SQL round trip
  (typically single-digit-to-tens of milliseconds) and request/autoload overhead. Not
  independently benchmarked; analytical estimate only. Revisit with a real
  before/after benchmark if this becomes a concern in practice.

## Implementation shape (not yet built)

- `LoadObjectFromTableRow()` branches on `$this->strictTypes` as shown above.
- New small helper (e.g. `CoerceValue($value, $type, $field)`) holding the cast+throw logic,
  keeping it out of the main loop body.
- `src/version` bumped to `2.2.5`, `src/changelog.md` gets a **new** entry documenting the
  feature with an explicit "opt-in via `$strictTypes`, off by default" callout — same style as
  the 2.2.4 cookie-auth entry's opt-in callout.
- Docs: check `docs/lib/DocMap.php` for the `MagratheaModel` narrative page in `docs/mds/*.md`
  and update it to describe `$strictTypes`; sanity-check `skills.MD` §3 (field types table)
  for whether it needs a `$strictTypes` mention too.

## Status

Scoped and agreed. Not yet implemented — build when ready to pick this up.
