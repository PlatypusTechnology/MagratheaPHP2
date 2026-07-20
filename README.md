# MagratheaPHP2

Version 2 of the acclaimed Magrathea PHP Framework — a lightweight PHP MVC framework with a
built-in ORM, database abstraction, REST API tooling, authentication, caching, mail, logging,
and other utilities needed to build web applications without pulling in a large stack of
dependencies.

## Installation

```bash
composer require platypustechnology/magratheaphp2
```

## Features

- **MVC core** (`MagratheaPHP`, `Bootstrap`) — routing, controllers, and application bootstrap.
- **ORM & database layer** (`DB`, `MagratheaModel`, `MagratheaModelControl`) — models, query
  building, and database control without hand-written SQL for common cases.
- **REST API tooling** (`MagratheaApi`, `MagratheaApiControl`, `MagratheaApiAuth`) — build and
  authenticate JSON APIs.
- **Authentication** (`Authentication.php`) — session/user auth primitives.
- **Utilities** — caching (`MagratheaCache`), mail (`MagratheaMail`, `MagratheaMailSMTP`),
  logging (`Logger`), debugging (`Debugger`), compressors, pagination, and a config system
  (`Config`, `ConfigApp`, `ConfigFile`).
- **Admin scaffolding** (`Admin/`) — building blocks for admin panels.

## Documentation

Full documentation — class references, method signatures (kept live via PHP Reflection), and
narrative usage guides — is available online:

**https://www.platypusweb.com.br/magratheaphp2/docs/**

The documentation site's source also ships in this repository under [`docs/`](docs/index.php)
(PHP-rendered; point a web server or `php -S` at that folder to browse it locally), with its
narrative source files in [`docs/mds/`](docs/mds/index.md).

## License

MIT
