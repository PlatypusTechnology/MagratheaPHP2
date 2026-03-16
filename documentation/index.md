# MagratheaPHP2 — Documentation

> A full PHP framework for building APIs, web applications, and admin panels.
>
> **Author:** Paulo Henrique Martins / Platypus Technology
> **License:** MIT
> **Namespace root:** `Magrathea2`
> **Official docs:** https://www.platypusweb.com.br/magratheaphp2

---

## What is MagratheaPHP2?

MagratheaPHP2 is a lightweight, opinionated PHP framework designed around rapid API development. It ships with:

- An **ORM** (Model + ModelControl) for MySQL via MySQLi
- A **fluent Query Builder** (SELECT, INSERT, UPDATE, DELETE)
- A **RESTful API framework** with CORS, JWT, and caching out of the box
- An **Admin Panel** system with pluggable features (CRUD, file editor, user management, etc.)
- **INI-based configuration** with multi-environment support
- **File-based logging** and flexible debugging
- **Email** (native + SMTP via PHPMailer)
- **CSS/JS compression**

---

## Documentation Index

### Getting Started
- [Installation & Quick Start](getting-started.md)

### Core
| File | Description |
|------|-------------|
| [MagratheaPHP](core/magrathea-php.md) | Main entry point, app bootstrap |
| [Config](core/config.md) | INI-based configuration |
| [Singleton](core/singleton.md) | Base singleton pattern |
| [MagratheaHelper](core/helper.md) | Utility / helper functions |
| [Global Functions](core/global-functions.md) | Procedural helpers & autoloader |

### Database Layer
| File | Description |
|------|-------------|
| [Database](database/database.md) | MySQLi connection wrapper |
| [Query Builder](database/query-builder.md) | Fluent SQL query construction |
| [MagratheaModel](database/orm-model.md) | ORM base model class |
| [MagratheaModelControl](database/orm-control.md) | Static ORM query interface |

### API Framework
| File | Description |
|------|-------------|
| [MagratheaApi](api/magrathea-api.md) | RESTful API router & runner |
| [MagratheaApiControl](api/api-controller.md) | API controller base class |
| [Authentication](api/authentication.md) | JWT token generation & validation |

### Admin Panel
| File | Description |
|------|-------------|
| [Admin System Overview](admin/admin.md) | Admin panel manager |
| [AdminManager](admin/admin-manager.md) | Admin singleton & runtime |
| [Admin Features](admin/admin-features.md) | Pluggable admin feature system |

### Utilities
| File | Description |
|------|-------------|
| [MagratheaCache](utilities/cache.md) | Response caching |
| [MagratheaMail](utilities/mail.md) | Email sending |
| [Logger](utilities/logger.md) | File-based logging |
| [Debugger](utilities/debugger.md) | Debug mode manager |
| [Compressors](utilities/compressors.md) | CSS & JS compression |

### Error Handling
| File | Description |
|------|-------------|
| [Exceptions](exceptions/exceptions.md) | Exception hierarchy |

### Advanced
| File | Description |
|------|-------------|
| [Design Patterns](advanced/patterns.md) | Patterns used across the framework |
| [Testing](advanced/testing.md) | Built-in test utilities |

---

## Minimum Requirements

- PHP 8.0+
- MySQL / MariaDB
- Composer

## Dependencies

| Package | Purpose |
|---------|---------|
| `firebase/php-jwt` | JWT token encode/decode |
| `phpmailer/phpmailer` | SMTP email support |
| `scssphp/scssphp` | SCSS compilation |
| `tedivm/jshrink` | JavaScript minification |
| `components/jquery` | Admin panel JS |
| `twbs/bootstrap` | Admin panel CSS |
