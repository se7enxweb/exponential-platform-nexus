# 7. Databases

Exponential Platform Nexus keeps its content repository, its Netgen Layouts layouts and rules, and a handful of
bundle tables in one relational database reached through Doctrine DBAL. Three engines are supported on every line:
MySQL or MariaDB, PostgreSQL, and SQLite. This chapter explains how each line is told which database to use (a set of
`DATABASE_*` parameters on 1.0.0.x, one `DATABASE_URL` on the Symfony 5.4 and 7.4 lines), what the schema looks like
on each line, including the move from the `ez*` tables to the `ibexa_*` tables on 1.3.0.x, how the schema is
installed and migrated, and how to back each engine up. It spends time on SQLite, because the live Nexus v5 reference
installation runs on a single SQLite file, and closes with the notes that matter when a site moves between engines.

[Previous: 6. Serving the site](06-serving-the-site.md) · [Next: 8. Configuration](08-configuration.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [Which engine, on which line](#71-which-engine-on-which-line)
2. [How a line is told where the database is](#72-how-a-line-is-told-where-the-database-is)
   1. [1.0.0.x: DATABASE_* parameters in app/config](#721-100x-database_-parameters-in-appconfig)
   2. [1.1.0.x, 1.2.0.x and 1.3.0.x: DATABASE_URL](#722-110x-120x-and-130x-database_url)
   3. [The DATABASE_URL format](#723-the-database_url-format)
   4. [serverVersion, charset and collation](#724-serverversion-charset-and-collation)
   5. [The legacy kernel shares the same database](#725-the-legacy-kernel-shares-the-same-database)
3. [The schema on each line](#73-the-schema-on-each-line)
   1. [Repository tables: ez* up to 1.2.0.x, ibexa_* on 1.3.0.x](#731-repository-tables-ez-up-to-120x-ibexa_-on-130x)
   2. [Netgen Layouts tables: nglayouts_*](#732-netgen-layouts-tables-nglayouts_)
   3. [Other bundle tables](#733-other-bundle-tables)
4. [Installing the schema](#74-installing-the-schema)
   1. [The install command per line](#741-the-install-command-per-line)
   2. [What the 1.3.0.x installer does](#742-what-the-130x-installer-does)
5. [Migrations](#75-migrations)
6. [MySQL and MariaDB](#76-mysql-and-mariadb)
7. [PostgreSQL](#77-postgresql)
8. [SQLite](#78-sqlite)
   1. [How the reference installation is configured](#781-how-the-reference-installation-is-configured)
   2. [The file, its directory and its owner](#782-the-file-its-directory-and-its-owner)
   3. [Journal mode, concurrency and when SQLite is enough](#783-journal-mode-concurrency-and-when-sqlite-is-enough)
   4. [SQLite on 1.0.0.x](#784-sqlite-on-100x)
9. [Backups and restores](#79-backups-and-restores)
10. [Switching an existing site to another engine](#710-switching-an-existing-site-to-another-engine)
11. [Checklist](#711-checklist)
12. [References](#712-references)

Commands in this chapter are run from the project root, the directory that holds `composer.json` and `bin/console`.
Commands that write to the database are shown, not run for you: read what they do before running them on a site
that matters, and take a backup first ([section 7.9](#79-backups-and-restores)).

---

## 7.1 Which engine, on which line

| | 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|
| Upstream base | eZ Platform 2.5, Symfony 3.4 | eZ Platform 3.3, Symfony 5.4 | Ibexa OSS 4.6, Symfony 5.4 | Ibexa 5 core, Symfony 7.4 |
| Where the connection is configured | `app/config/parameters.yml` (`env(DATABASE_*)`) | `.env` / `.env.local` (`DATABASE_URL`) | `.env` / `.env.local` (`DATABASE_URL`) | `.env` / `.env.local` (`DATABASE_URL`) |
| Default shipped in the repository | `pdo_mysql` (`parameters.yml.dist`) | PostgreSQL URL in `.env` | PostgreSQL URL in `.env` | PostgreSQL URL in `.env` |
| Repository table prefix | `ez` | `ez` | `ez` (plus a few `ibexa_` tables) | `ibexa_` |
| Netgen Layouts tables | `nglayouts_*` | `nglayouts_*` | `nglayouts_*` | `nglayouts_*` |
| Install command | `ezplatform:install` | `exponential:install` (the project's, since `v1.1.0.7`), and the upstream `ibexa:install` (alias `ezplatform:install`) | `exponential:install` (the project's), and the upstream `ibexa:install` (alias `ezplatform:install`) | `exponential:install` (the core package's) |

The table is taken from each branch's committed configuration and from the four reference installations on the
development server (`site.v2.nexus`, `site.v3.nexus`, `site.v4.nexus` and `site.v5.nexus`), whose SQLite files were
read, never written, while this chapter was prepared.

Which engine to choose:

- **MySQL 8.0 or MariaDB 10.3 and later** is what upstream develops against and what every line's default charset and
  collation settings are written for (`utf8mb4`, `utf8mb4_unicode_520_ci`). Choose it for a production site with
  editors working in parallel, and when you want the widest choice of hosting and tooling.
- **PostgreSQL 14 and later** is equally supported by Doctrine and by the installers, which carry PostgreSQL schema
  and data files. The Symfony 5.4 and 7.4 lines even ship a PostgreSQL URL as the active default in `.env`, and the
  `compose.yaml` on every branch starts a `postgres:16-alpine` container.
- **SQLite 3.35 and later** needs no server: the database is a single file under `var/`. It is the quickest way to a
  working installation and is what the Nexus v5 reference installation runs on. It is a good fit for development,
  demonstration and small sites with few concurrent editors; read [section 7.8.3](#783-journal-mode-concurrency-and-when-sqlite-is-enough)
  before you put a busy site on it.

The minimum versions are the ones the branch READMEs and `doc/sevenx/INSTALL.md` state for 1.1.0.x to 1.3.0.x
(MySQL 8.0, MariaDB 10.3, PostgreSQL 14, SQLite 3.35). They were not tested one by one for this book. The
requirement chapter, [2. Requirements](02-requirements.md), lists the PHP extensions: `pdo_mysql`, `pdo_pgsql` or
`pdo_sqlite`, matching the engine.

---

## 7.2 How a line is told where the database is

### 7.2.1 1.0.0.x: DATABASE_* parameters in app/config

The 1.0.0.x line (and `master`, which carries the same Symfony 3.4 layout) boots `AppKernel`, which loads
`app/config/<environment>/config.yml`. The Doctrine connection is built from individual parameters, not from a URL.
From [`app/config/config.yml`](../../app/config/config.yml) on 1.0.0.x:

```yaml
doctrine:
    dbal:
        connections:
            default:
                driver: '%database_driver%'
                host: '%database_host%'
                port: '%database_port%'
                dbname: '%database_name%'
                user: '%database_user%'
                password: '%database_password%'
                charset: '%database_charset%'
                # Only used with pdo_sqlite driver; ignored by MySQL/MariaDB/PostgreSQL.
                path: '%database_path%'
                server_version: '%database_version%'
                default_table_options:
                    charset: '%database_charset%'
                    collate: '%database_collation%'
```

[`app/config/default_parameters.yml`](../../app/config/default_parameters.yml) maps each `database_*` parameter to an
environment variable (`database_driver: '%env(DATABASE_DRIVER)%'` and so on), and
[`app/config/parameters.yml.dist`](../../app/config/parameters.yml.dist), copied to `parameters.yml` at install time,
gives those variables their defaults:

```yaml
parameters:
    env(DATABASE_DRIVER): pdo_mysql
    env(DATABASE_HOST): localhost
    env(DATABASE_PORT): ~
    env(DATABASE_NAME): ezplatform
    env(DATABASE_USER): root
    env(DATABASE_PASSWORD): ~
    env(DATABASE_CHARSET): utf8mb4
    env(DATABASE_COLLATION): utf8mb4_unicode_520_ci
    env(DATABASE_VERSION): mariadb-10.2.26
```

So you can set the database either by editing `app/config/parameters.yml` or by exporting real environment
variables (`DATABASE_HOST=...`) in the web server or PHP-FPM pool and the shell that runs `bin/console`; a real
environment variable wins over the `env(...)` default.

Three more things on this line:

- **`database_path`** is the SQLite file. 1.0.0.x sets it in `default_parameters.yml` to
  `%kernel.project_dir%/var/data_%kernel.environment%.db` (for example `var/data_dev.db`). `master` does not have the
  `path:` line or the parameter; see [section 7.8.4](#784-sqlite-on-100x).
- **Platform.sh**: [`app/config/env/platformsh.php`](../../app/config/env/platformsh.php) reads
  `PLATFORM_RELATIONSHIPS` and sets `database_driver`, `database_host`, `database_port` and `database_name` from it.
- **DFS clusters**: [`app/config/env/generic.php`](../../app/config/env/generic.php) switches on the DFS file handler
  when `DFS_NFS_PATH` is set and then reads `DFS_DATABASE_DRIVER`, `DFS_DATABASE_HOST`, `DFS_DATABASE_PORT` and the
  related variables, falling back to the main `database_*` parameters for each one that is not set.

The `.env` file in the project root on this line is not read by the application: it holds variables for the Docker
Compose files under `doc/docker/` (`COMPOSE_FILE`, `DATABASE_USER`, `DATABASE_NAME` and image names). The
`config/packages/` directory that also exists on `master` is not loaded by `AppKernel` either.

### 7.2.2 1.1.0.x, 1.2.0.x and 1.3.0.x: DATABASE_URL

The Symfony 5.4 and 7.4 lines use the Symfony Flex layout. `config/packages/doctrine.yaml` reads one variable:

```yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # IMPORTANT: You MUST configure your server version,
        # either here or in the DATABASE_URL env var (see .env file)
        #server_version: '16'

        use_savepoints: true

        schema_filter: ~^(?!nglayouts_)~
```

That block is the same on all three branches and on the v5 reference installation. On 1.3.0.x the ORM section adds
`identity_generation_preferences` so that PostgreSQL uses identity columns.

`DATABASE_URL` is read from the environment, with the usual Symfony order: `.env` (committed defaults), `.env.local`
(your machine, never committed), `.env.$APP_ENV` and `.env.$APP_ENV.local`, and a real environment variable wins
over all of them. Put your connection in `.env.local`, or in the web server's and the CLI's environment, not in
`.env`. [Chapter 8](08-configuration.md) covers the `.env` files and Symfony secrets in full.

The `DATABASE_*` variables that are still present in `.env` on these lines do different jobs:

| Variable | Read by | Effect |
|---|---|---|
| `DATABASE_URL` | `config/packages/doctrine.yaml` | the connection itself |
| `DATABASE_CHARSET` | `ibexa_doctrine_schema.yaml` (`ez_doctrine_schema.yaml` on 1.1.0.x) | table charset when the installer creates tables |
| `DATABASE_COLLATION` | the same file | table collation, MySQL and MariaDB only |
| `DATABASE_VERSION` | nothing in `config/` on 1.1.0.x, 1.2.0.x and 1.3.0.x | none; put `serverVersion` into the URL instead |

`doc/sevenx/INSTALL.md` of 1.1.0.x to 1.3.0.x showed, up to `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5`, a block of
`DATABASE_DRIVER`, `DATABASE_HOST`, `DATABASE_PORT`, `DATABASE_NAME`, `DATABASE_USER` and `DATABASE_PASSWORD`
variables "or a full DSN (takes precedence)". On these lines nothing in `config/` reads those six variables; only
`DATABASE_URL` sets the connection. The guides of the releases of 5 October 2026 write `DATABASE_URL`.

### 7.2.3 The DATABASE_URL format

The format is Doctrine's connection URL:

```text
<driver>://<user>:<password>@<host>:<port>/<database>?serverVersion=<version>&charset=<charset>
```

The examples the 1.3.0.x `.env` carries, with the password replaced:

```dotenv
# SQLite: a file under var/, one per environment
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
# MySQL 8
DATABASE_URL="mysql://app:CHANGE_ME@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
# MariaDB
DATABASE_URL="mysql://app:CHANGE_ME@127.0.0.1:3306/app?serverVersion=10.11.2-MariaDB&charset=utf8mb4"
# PostgreSQL
DATABASE_URL="postgresql://app:CHANGE_ME@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
```

Notes:

- The `resolve:` processor in `doctrine.yaml` replaces container parameters inside the value, which is how
  `%kernel.project_dir%` and `%kernel.environment%` in the SQLite URL become a real path.
- A SQLite URL has three slashes before an absolute path: `sqlite:///` followed by `/var/www/.../var/data_prod.db`.
- If the user name or password contains a character that has a meaning in a URL (`@`, `:`, `/`, `#`, `?`, `%`),
  percent-encode it, or the URL is split in the wrong place.
- 1.1.0.x and 1.2.0.x show the SQLite example as `var/data.db` (no environment suffix); 1.3.0.x and the v5 reference
  installation use `var/data_%kernel.environment%.db`. Either works; the file name is whatever the URL says.
- A connection through a Unix socket is written with an empty host and the socket as a query parameter, for example
  `mysql://app:CHANGE_ME@localhost/app?unix_socket=/var/lib/mysql/mysql.sock&serverVersion=8.0.32`. This follows
  Doctrine's URL rules and was not tested on a Nexus installation for this book.

### 7.2.4 serverVersion, charset and collation

**serverVersion.** Doctrine needs to know the server version before it connects, to pick the right SQL platform. If it
is missing, Doctrine opens a connection just to ask, which fails during `cache:warmup` or a build without a database.
Set it on every line:

- 1.0.0.x: `DATABASE_VERSION`, in the form `mariadb-10.2.26` for MariaDB or `5.7` / `8.0` for MySQL.
- 1.1.0.x to 1.3.0.x: `serverVersion=` in `DATABASE_URL`. For MariaDB, 1.3.0.x's `.env` uses the form
  `10.11.2-MariaDB`; for MySQL a plain version such as `8.0.32`; for PostgreSQL the major version, such as `16`.
  SQLite does not need it.

Give the version of the server you actually run. A version that is too low makes Doctrine generate older SQL; a
version that is too high can make it use features the server does not have.

**charset.** Use `utf8mb4` on MySQL and MariaDB (every line's default), never the three-byte `utf8`, or characters
outside the Basic Multilingual Plane (many emoji, some CJK) are rejected or cut. On PostgreSQL the URL says
`charset=utf8` and the database itself is created with `ENCODING 'UTF8'`.

**collation.** Every line defaults to `utf8mb4_unicode_520_ci`. The collation is applied when the installer creates
tables (`default_table_options` on 1.0.0.x, `ibexa_doctrine_schema.tables.options` on the later lines); it has no
effect on PostgreSQL or SQLite. Create the database with the same collation so tables added later by migrations
match ([section 7.6](#76-mysql-and-mariadb)).

### 7.2.5 The legacy kernel shares the same database

On the lines that carry the Exponential legacy kernel through a legacy bridge (1.0.0.x, 1.1.0.x and 1.2.0.x), the legacy
kernel does not have its own database settings. The bridge (`se7enxweb/legacy-bridge`,
`bundle/LegacyMapper/Configuration.php`) reads the Doctrine connection's parameters at every legacy kernel build and
injects them into `site.ini [DatabaseSettings]`:

| Doctrine parameter | Legacy setting |
|---|---|
| `host`, `port`, `user`, `password`, `dbname`, `unix_socket` | `Server`, `Port`, `User`, `Password`, `Database`, `Socket` |
| `driver` | `DatabaseImplementation`: `pdo_mysql` becomes `ezmysqli`, `pdo_pgsql` becomes `ezpostgresql`, `oci8` becomes `ezoracle`, `pdo_sqlite` becomes `sqlite3` |
| `path` (SQLite only) | `Database`, the absolute path of the shared file |

This was read in the bridge installed on the v2 (1.0.0.x) and v3 (1.1.0.x) reference installations, and in
`se7enxweb/ibexa-legacy-bridge` (`bundle/LegacyMapper/Configuration.php`) on the v4 (1.2.0.x) one, which maps the
same parameters the same way. Do not set database values in the legacy `settings/override/site.ini.append.php` on these lines; they are overwritten. On SQLite,
both kernels open the same file, and the legacy side then uses Exponential's own SQLite driver, which the
[Exponential 6 book, chapter 9](https://github.com/se7enxweb/exponential/blob/main/doc/install/09-databases.md)
describes in detail.

---

## 7.3 The schema on each line

### 7.3.1 Repository tables: ez* up to 1.2.0.x, ibexa_* on 1.3.0.x

Up to and including 1.2.0.x, the content repository uses the table names inherited from the upstream kernel (the `ez` prefix): `ezcontentobject`,
`ezcontentobject_attribute`, `ezcontentobject_tree`, `ezcontentclass`, `ezurlalias_ml`, `ezuser` and so on. The
reference installations show:

| Reference installation (line) | `ez*` tables | `ibexa_*` tables | `nglayouts_*` tables |
|---|---|---|---|
| v2 (1.0.0.x) | 126 | 0 | 23 |
| v3 (1.1.0.x) | 52 | 1 (`ibexa_setting`) | 23 |
| v4 (1.2.0.x) | 52 | 5 (`ibexa_setting`, `ibexa_token`, `ibexa_token_type`, `ibexa_user_invitations`, `ibexa_user_invitations_assignments`) | 23 |
| v5 (1.3.0.x) | 5 (bundle tables only, see 7.3.3) | 56 | 23 |

The v2 count is larger because the 1.0.0.x installation also carries the full Exponential Platform Legacy kernel
schema (shop, workflow, collaboration and the other legacy-only tables).

On 1.3.0.x the core repository schema is defined in `schema.yaml` of the core package
(`vendor/se7enxweb/exponential-platform-dxp-core/src/bundle/Core/Resources/config/storage/legacy/schema.yaml` on the
reference installation, 52 tables), and every table carries the `ibexa_` prefix. Some columns were renamed with the
tables. Comparing the v4 and v5 reference files column by column gives:

| Up to 1.2.0.x | 1.3.0.x | Column changes |
|---|---|---|
| `ezcontentobject` | `ibexa_content` | `contentclass_id` is now `content_type_id` |
| `ezcontentobject_attribute` | `ibexa_content_field` | `contentclassattribute_id` is now `content_type_field_definition_id` |
| `ezcontentobject_version` | `ibexa_content_version` | none |
| `ezcontentobject_name` | `ibexa_content_name` | none |
| `ezcontentobject_tree` | `ibexa_content_tree` | none |
| `ezcontentobject_link` | `ibexa_content_relation` | `contentclassattribute_id` is now `content_type_field_definition_id` |
| `ezcontentclass` | `ibexa_content_type` | `version` is now `status` |
| `ezurlalias_ml` | `ibexa_url_alias_ml` | none |
| `ezuser` | `ibexa_user` | none |
| `ezimagefile` | `ibexa_image_file` | none |
| `ezdfsfile` | `ibexa_dfs_file` | none |
| `ezsearch_word` | `ibexa_search_word` | none |

This matters to anyone who keeps hand-written SQL, reports, or `SELECT`s in monitoring scripts: every one of them
has to be rewritten for 1.3.0.x. Code that goes through the PHP API or the Site API is not affected. Moving a
1.2.0.x database to 1.3.0.x is an upgrade, covered in the upgrading chapter; the upstream schema change is described in
the Ibexa 5.0 update documentation (see [References](#712-references)).

### 7.3.2 Netgen Layouts tables: nglayouts_*

Netgen Layouts keeps its data in 23 tables named `nglayouts_*` on every line (`nglayouts_layout`, `nglayouts_zone`,
`nglayouts_block`, `nglayouts_rule`, `nglayouts_collection` and so on), including its own migration table,
`nglayouts_migration_versions`. These tables have the same names on every line; Netgen Layouts did not follow the
`ibexa_` rename.

On the Symfony 5.4 and 7.4 lines, `doctrine.yaml` sets `schema_filter: ~^(?!nglayouts_)~`. That hides the Layouts
tables from Doctrine's schema tools, so `doctrine:schema:validate`, `doctrine:schema:update` and
`doctrine:migrations:diff` for the application's own entities neither report nor try to drop them. Keep the filter
when you edit the file.

### 7.3.3 Other bundle tables

Bundles add their own tables next to the repository. On the v5 reference installation these are:

- `eztags`, `eztags_attribute_link`, `eztags_keyword`: Netgen Tags. The bundle keeps its historic names on 1.3.0.x.
- `ezinfocollection`, `ezinfocollection_attribute`: Netgen Information Collection.
- `sckenhancedselection`: the Enhanced Selection field type.
- `novaseo_meta`: Novactive SEO.
- `nguser_setting`: Netgen site bundle user settings.
- `ibexa_messenger_messages`, `ibexa_messenger_lock_keys`: the queue and lock tables of `ibexa/messenger`, whose
  default transport is `doctrine://ibexa.current?table_name=ibexa_messenger_messages`, that is, the repository's own
  connection.

The v5 reference file holds 88 tables and 181 indexes in all. There is no `doctrine_migration_versions` table there,
because the project's own `migrations/` directory is empty ([section 7.5](#75-migrations)).

---

## 7.4 Installing the schema

[Chapter 4](04-installing.md) walks through a complete installation; this section only says what the install
command does to the database. Every installer below **drops the tables it manages before it creates them**: run one
against an existing site only when you mean to start that site again from nothing.

### 7.4.1 The install command per line

| Line | Command | Install types registered |
|---|---|---|
| `master` | `php bin/console ezplatform:install cjw-exponential-media` | `cjw-exponential-media` (data from `se7enxweb/cjw-exponential-media-site-data`), plus those of the installed bundles |
| 1.0.0.x | `php bin/console ezplatform:install <type>` | `exponential-cjw` (from `src/AppBundle`), and on the v2 reference installation also `clean`, `exponential-oss`, `netgen-media`, `netgen-media-clean`, `netgen-media-remote-clean` |
| 1.1.0.x | `php bin/console exponential:install exponential-media` (`src/Command/ExponentialInstallCommand.php`, since `v1.1.0.7`) | `exponential:install` knows only the types tagged `exponential.installer`: `exponential-media`. The upstream `ibexa:install` (alias `ezplatform:install`) knows the types tagged `ezplatform.installer`: `clean`, `ibexa-oss`, `netgen-media`, `netgen-media-clean` |
| 1.2.0.x | `php bin/console exponential:install exponential-media` (`src/RepositoryInstaller/Command/`, since `v1.2.0.0`) | `exponential:install` collects every type tagged `ibexa.installer`: `exponential-media`, `ibexa-oss` (its default), `netgen-media` and the other types of `netgen/site-installer-bundle`; the upstream `ibexa:install` sees the same types |
| 1.3.0.x | `php bin/console exponential:install <type>` (from the core package) | `ibexa-oss` (the default), `exponential-oss`, `exponential-media`, and `netgen-media` from `netgen/site-installer-bundle` |

On 1.1.0.x and 1.2.0.x both commands exist side by side: the project's `exponential:install` (with SQLite support
and the media installer) and the platform's `ibexa:install`. Use `exponential:install` for the demo, as the READMEs
of both lines do; the earlier 1.1.0.x tags (`v1.1.0.0` to `v1.1.0.6`) have only `ibexa:install`, which is also what
the v3 reference installation, made from `v1.1.0.6`, shows. Before you install, list what your copy offers:

```bash
php bin/console list | grep -E ':install'
```

and run the command without a type to see the list of types in its help text (`php bin/console <command> --help`).

### 7.4.2 What the 1.3.0.x installer does

Read in `InstallPlatformCommand` and `CoreInstaller` of the core package on the v5 reference installation,
`exponential:install` does this, in order:

1. Checks that `public/` or `public/var/` is writable, and stops with exit status 7 if not.
2. On MySQL, MariaDB and PostgreSQL, runs `doctrine:database:create --if-not-exists` for the repository connection.
   On SQLite it skips that step, because the file is created on the first connection.
3. If the database already has tables, asks for confirmation ("Running this command will delete data in all Ibexa
   generated tables"). With `--no-interaction` the question is answered with its default.
4. Builds the repository schema from the `schema.yaml` files through the Doctrine schema builder, drops the existing
   tables of that schema and creates them again. On SQLite it substitutes its own platform class so that tables
   with a composite primary key do not get `AUTOINCREMENT` on a non-integer column.
5. Creates the Netgen Layouts tables from the engine-specific SQL file of `layouts-core` and, on SQLite, loads the
   package's `nglayouts_cleandata.sql`.
6. If an `ezpublish_legacy/kernel/sql/` directory exists in the project, creates the legacy-only tables from its
   engine-specific schema file, with every `CREATE TABLE` rewritten to `CREATE TABLE IF NOT EXISTS`.
7. Imports the install type's data and binary files, then, when run interactively, makes you change the default
   administrator password.
8. Clears the repository cache pool and runs `exponential:reindex`, unless you pass `--skip-indexing`.

**Step 5 depends on the core version.** Up to core `v5.0.7`, the version the lock file of `1.3.0.6` (as of `1.3.0.5`) and of the branch
pins and the one the v5 reference installation runs, step 5 looks only for `vendor/netgen/layouts-core/...`, while
this line installs the fork as `vendor/se7enxweb/layouts-core` (which `replace`s `netgen/layouts-core`). The step is
then skipped without a message, no `nglayouts_*` table is created, and loading the `exponential-media` data, which
holds about 1,200 `INSERT`s into those tables, fails. On the reference installation the Layouts tables were created
by the Layouts migrations instead: `nglayouts_migration_versions` lists the migrations up to `Version010300`. Core
`v5.0.9` (released 2026-10-05) checks `vendor/se7enxweb/layouts-core` first and `vendor/netgen/layouts-core` second,
prints `Importing Netgen Layouts schema from <file>`, and says so when it finds neither. Update the core before a
fresh install:

```bash
composer update se7enxweb/exponential-platform-dxp-core
```

If the `nglayouts_*` tables are missing after an install all the same, run the Layouts migrations as shown in the next
section.

When the database cannot be created, core `v5.0.7` tells you to check `app/config/parameters.yml`, a file 1.3.0.x
does not have; check `DATABASE_URL` in `.env.local` instead. Core `v5.0.9` names `.env.local` and `DATABASE_URL` in
the message itself.

---

## 7.5 Migrations

There are three separate sets of migrations; do not mix them up.

**The project's own Doctrine migrations.** Every branch has a `config/packages/doctrine_migrations.yaml` pointing the
namespace `DoctrineMigrations` at `%kernel.project_dir%/migrations`, and every branch ships that directory empty
(only a `.gitignore`). It is for your own entities under `src/Entity`. Generate and apply with the standard
DoctrineMigrationsBundle commands:

```bash
php bin/console doctrine:migrations:diff        # write a migration for changed entities
php bin/console doctrine:migrations:status      # what is applied, what is pending
php bin/console doctrine:migrations:migrate     # apply pending migrations
```

On 1.0.0.x `AppKernel` does not load `config/packages/`, so check `php bin/console list doctrine` before relying on
these commands there.

**Netgen Layouts migrations.** Layouts ships its own migration classes and its own configuration file, with the
table `nglayouts_migration_versions` and `transactional: false`. After an upgrade of the Layouts packages, apply them
with that configuration:

```bash
# up to 1.2.0.x, where the package is installed as netgen/layouts-core
php bin/console doctrine:migrations:migrate --configuration=vendor/netgen/layouts-core/migrations/doctrine.yaml
# 1.3.0.x, where the package is installed as se7enxweb/layouts-core
php bin/console doctrine:migrations:migrate --configuration=vendor/se7enxweb/layouts-core/migrations/doctrine.yaml
```

The first form is the one in [`doc/cjw/PACKAGE_CHANGELOG.md`](../cjw/PACKAGE_CHANGELOG.md); the configuration files
were found at those paths on the v2 to v4 and the v5 reference installations respectively.

**Content migrations.** Each line carries a content migration bundle: `se7enxweb/ezmigrationbundle ^5.9.4` on the
2.5 generation (`master`, `1.0.0.x`), `se7enxweb/ezmigrationbundle ^6.0` on 1.1.0.x,
`tanoconsulting/ibexa-migration-bundle ^1.0` on 1.2.0.x and `mrk-te/ibexa-migration-bundle2 ^3.0` on 1.3.0.x. These
migrate content and content types, not the schema; see
[chapter 5](05-the-demo-site-and-layouts.md) and the upstream `doc/netgen/IBEXA_MIGRATIONS.md` of each line.

---

## 7.6 MySQL and MariaDB

Create the database with the character set and collation the installer expects, and a user that owns it:

```sql
CREATE DATABASE nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'nexus'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT ALL PRIVILEGES ON nexus.* TO 'nexus'@'localhost';
```

On MySQL 8.0 and later, create the user and grant privileges in two statements as above. The single statement
`GRANT ... IDENTIFIED BY '...'` that `doc/sevenx/INSTALL.md` of 1.1.0.x to 1.3.0.x showed up to `v1.1.0.7`,
`v1.2.0.0` and `1.3.0.5` was removed in MySQL 8.0 and fails there; MariaDB still accepts it. The guides of the
releases of 2026-10-05 use the two statements.

Then point the line at it:

```dotenv
# 1.1.0.x to 1.3.0.x, in .env.local
DATABASE_URL="mysql://nexus:CHANGE_ME@127.0.0.1:3306/nexus?serverVersion=8.0.32&charset=utf8mb4"
```

```yaml
# 1.0.0.x, in app/config/parameters.yml
    env(DATABASE_DRIVER): pdo_mysql
    env(DATABASE_HOST): 127.0.0.1
    env(DATABASE_PORT): 3306
    env(DATABASE_NAME): nexus
    env(DATABASE_USER): nexus
    env(DATABASE_PASSWORD): CHANGE_ME
    env(DATABASE_VERSION): 8.0
```

Things that go wrong:

- **`Specified key was too long`** while the installer creates tables: the server runs an old row format or a
  storage engine other than InnoDB. Use InnoDB with the `DYNAMIC` row format (the default on MySQL 5.7 and later and
  MariaDB 10.2 and later).
- **Question marks instead of characters**, or "Incorrect string value": the connection or the tables are not
  `utf8mb4`. Check both the `charset` in the URL and `SHOW CREATE TABLE` of an affected table.
- **Mixed collations** ("Illegal mix of collations") after adding tables by hand: create them with
  `utf8mb4_unicode_520_ci`, like the rest.
- **The wrong `serverVersion`**: a MariaDB server described as MySQL, or the reverse, makes Doctrine emit SQL the
  server rejects in edge cases. Use the `-MariaDB` suffix form (1.1.0.x and later) or the `mariadb-` prefix form
  (1.0.0.x) for MariaDB.

---

## 7.7 PostgreSQL

Create a database in UTF-8 and a user that owns it:

```sql
CREATE USER nexus WITH PASSWORD 'CHANGE_ME';
CREATE DATABASE nexus OWNER nexus ENCODING 'UTF8';
```

Making the application user the **owner** of the database (rather than only granting `ALL PRIVILEGES ON DATABASE`)
matters on PostgreSQL 15 and later, where ordinary users can no longer create tables in the `public` schema of a
database they do not own. The `GRANT ALL PRIVILEGES ON DATABASE` form that the older `doc/INSTALL.md` and
`doc/sevenx/INSTALL.md` showed (up to `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5`) is not enough there on its own; the
guides of the releases of 2026-10-05 create the database with the application user as its owner.

```dotenv
DATABASE_URL="postgresql://nexus:CHANGE_ME@127.0.0.1:5432/nexus?serverVersion=16&charset=utf8"
```

On 1.0.0.x use `env(DATABASE_DRIVER): pdo_pgsql` and the other `DATABASE_*` values. Every installer carries
PostgreSQL schema and data files (`data/postgresql/` of the 1.3.0.x core package, `schema.pgsql.sql` of
`layouts-core`). The `compose.yaml` of every branch starts `postgres:16-alpine` for local development, and on
1.0.0.x [`doc/docker/db-postgresql.yml`](../docker/db-postgresql.yml) is the Compose override that switches the
Docker stack to PostgreSQL (its README calls it experimental).

Collation settings (`DATABASE_COLLATION`) have no effect on PostgreSQL; sorting follows the database's locale, which
is fixed when the database is created.

---

## 7.8 SQLite

### 7.8.1 How the reference installation is configured

The live Nexus v5 reference installation (`site.v5.nexus.alpha.se7enx.com`, line 1.3.0.x) runs entirely on SQLite.
Its configuration, with nothing secret in it:

```dotenv
# .env
APP_ENV=dev
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
DATABASE_CHARSET=utf8mb4
DATABASE_COLLATION=utf8mb4_unicode_520_ci
SEARCH_ENGINE=legacy
```

- `config/packages/doctrine.yaml` is the stock file shown in [section 7.2.2](#722-110x-120x-and-130x-database_url),
  with no SQLite-specific options. `DATABASE_CHARSET` and `DATABASE_COLLATION` are harmless on SQLite.
- With `APP_ENV=dev` the URL resolves to `var/data_dev.db`. The file is about 6.5 MB and holds 88 tables, 290 content
  objects and 22 layouts.
- `SEARCH_ENGINE=legacy` keeps search in the same database (`ibexa_search_word`, `ibexa_search_object_word_link`);
  there is no Solr to run alongside.
- The application's own `config/packages/messenger.yaml` defines only a `sync://` transport, so no worker is needed
  for application messages. `doc/sevenx/INSTALL.md` recommends `MESSENGER_TRANSPORT_DSN=sync://` for SQLite; the
  reference installation leaves the variable at `doctrine://default?auto_setup=0`, which no configuration file there
  uses.

If you change `APP_ENV` to `prod`, the same URL points to `var/data_prod.db`, a different and possibly empty file.
Either copy the database to the new name or give `DATABASE_URL` a fixed file name, such as
`sqlite:///%kernel.project_dir%/var/nexus.db`, before you switch.

### 7.8.2 The file, its directory and its owner

SQLite needs write access to **the file and the directory it is in**: it creates the journal (`-journal`, or `-wal`
and `-shm` in WAL mode) next to the database file at every write. On the reference installation the file is
`alpha:psaserv` with mode `660`, in a `var/` directory every process can write to.

Make one group shared by every process that touches the file (the PHP-FPM pool, Exponential Velocity's workers, and
the shell user that runs `bin/console`), and give the directory the setgid bit so new files inherit it:

```bash
chgrp -R nexus var
chmod 2775 var
chmod 660 var/data_prod.db
```

The installer runs as the shell user, so after `exponential:install` the file belongs to that user; fix the owner or
group before the web server tries its first write. A database that is readable but not writable by the web server
gives "attempt to write a readonly database" on the first save in the back office. The [Exponential 6 book,
chapter 9](https://github.com/se7enxweb/exponential/blob/main/doc/install/09-databases.md) covers the mixed-user case
in detail; [chapter 6](06-serving-the-site.md) covers the users each server runs as.

Keep the database file out of the document root: `var/` is beside `public/`, not under it, which is right.

### 7.8.3 Journal mode, concurrency and when SQLite is enough

The reference database runs in SQLite's default rollback-journal mode (`PRAGMA journal_mode` returns `delete`), with
a 4096-byte page size. Nothing in the Nexus Doctrine configuration changes the journal mode or other PRAGMAs.

What that means in practice:

- SQLite allows **one writer at a time** for the whole file. A publish in the back office holds the write lock for
  the length of its transaction; a second editor saving at the same moment waits or gets "database is locked".
- In rollback-journal mode, a writer also blocks readers while it commits. In **WAL mode** readers continue during
  a write. WAL is a property of the file and persists once set:

  ```bash
  sqlite3 var/data_prod.db 'PRAGMA journal_mode=WAL;'
  ```

  Do this with the site stopped, and make sure the directory is writable for every process, because WAL adds the
  `-wal` and `-shm` files next to the database. This was not done on the reference installation, and its effect on
  Nexus was not measured for this book.
- Reads are fast and need no server round trip. The HTTP cache and the persistence cache pool ([chapter
  10](10-operations.md)) take most page views away from the database anyway.

SQLite suits development, demonstrations, staging copies and small sites with a few editors. For a site with several
editors publishing at the same time, imports, or more than one application server, use MySQL, MariaDB or
PostgreSQL. A SQLite file must never be shared over NFS or another network file system between servers.

The 1.1.0.x and 1.2.0.x READMEs describe SQLite-specific fixes in their installers and gateways
(`ExponentialMediaInstaller::fixSqliteCompositePrimaryKeys()` on 1.1.0.x, `ExponentialSqliteGateway` on 1.2.0.x) for
composite primary keys and identifier generation. They exist because Doctrine's SQLite platform handles those cases
differently from MySQL; they are one more reason to test a SQLite site's editing workflow before relying on it.

### 7.8.4 SQLite on 1.0.0.x

On 1.0.0.x set the driver to SQLite in `app/config/parameters.yml`:

```yaml
    env(DATABASE_DRIVER): pdo_sqlite
```

The file is `database_path`, which `default_parameters.yml` sets to `var/data_<environment>.db`. Two remarks from
reading the 1.0.0.x files:

- `database_path` is a fixed string, not `%env(DATABASE_PATH)%`, so no environment variable moves the file. Override
  it by setting `database_path:` in `app/config/parameters.yml`, which is imported after `default_parameters.yml`:

  ```yaml
      database_path: /var/www/nexus/var/nexus.db
  ```

  Up to the release `1.0.0.9` the comments in both files claimed a `DATABASE_PATH` environment variable would work;
  the branch corrected them on 2026-10-05 (released in `1.0.0.11`).
- On `master`, `config.yml` has no `path:` line and no `database_path` parameter, although the install guide
  released with `v2.5.0.6` says the file is created at `var/data_dev.db`. Use the 1.0.0.x configuration for SQLite.

The legacy kernel opens the same file through the bridge ([section 7.2.5](#725-the-legacy-kernel-shares-the-same-database)).

---

## 7.9 Backups and restores

A complete backup of a Nexus site is three things taken at the same moment: **the database**, **the binary files**
(the storage directory of the install type: `public/var/site/storage` for 1.3.0.x's `exponential-media` type,
`ezpublish_legacy/var/site/storage` for `master`'s `cjw-exponential-media` type; check `var_dir` in your siteaccess
configuration), and **your configuration** (`.env.local`, `app/config/parameters.yml`, any secrets vault). Caches
under `var/cache` are not part of a backup; they are rebuilt.

Write backups to a directory outside the project and outside the document root, such as `/var/backups/nexus`, with
permissions that keep them from other users: a database dump contains password hashes and editors' e-mail addresses.

**MySQL and MariaDB.** `--single-transaction` gives a consistent dump of InnoDB tables without locking the site:

```bash
mysqldump --single-transaction --routines --default-character-set=utf8mb4 \
  -u nexus -p nexus | gzip > /var/backups/nexus/nexus-$(date +%F).sql.gz
# restore into an empty database created as in section 7.6
gunzip -c /var/backups/nexus/nexus-2026-10-05.sql.gz | mysql --default-character-set=utf8mb4 -u nexus -p nexus
```

On MariaDB the tools are also called `mariadb-dump` and `mariadb`.

**PostgreSQL.** The custom format can be restored selectively and in parallel:

```bash
pg_dump -Fc -U nexus nexus > /var/backups/nexus/nexus-$(date +%F).dump
pg_restore --no-owner -U nexus -d nexus /var/backups/nexus/nexus-2026-10-05.dump
```

**SQLite.** Never copy a SQLite file with `cp` while the site can write to it; the copy may be torn. Use the online
backup of the `sqlite3` tool, which takes a consistent copy while the site runs:

```bash
sqlite3 var/data_prod.db ".backup '/var/backups/nexus/data_prod-$(date +%F).db'"
sqlite3 /var/backups/nexus/data_prod-2026-10-05.db 'PRAGMA integrity_check;'   # prints ok
```

To restore, stop the site (or Exponential Velocity's workers and PHP-FPM), copy the backup over the file, remove a
stale `-journal`, `-wal` or `-shm` file beside it, fix owner and mode ([section 7.8.2](#782-the-file-its-directory-and-its-owner)),
and start again. A plain-text dump (`sqlite3 var/data_prod.db .dump`) is also useful, for diffing or for loading into
a new file.

After any restore, clear the application cache and the HTTP cache ([chapter 10](10-operations.md)) and, with Solr,
reindex: cached pages and search documents otherwise still describe the content that was there before.

---

## 7.10 Switching an existing site to another engine

There is no Nexus command that converts a database from one engine to another. The paths that work:

1. **Reinstall and re-import content.** Install a fresh site on the new engine with the same install type and
   versions, then move content with the content migration bundle or through the REST API. This is the cleanest way
   and the only one that is engine-neutral.
2. **Convert the data with a general tool.** The READMEs of 1.1.0.x to 1.3.0.x list tools for this (`pgloader` for
   MySQL or SQLite to PostgreSQL, `sqlite3-to-mysql` for SQLite to MySQL, `mysql2sqlite` for the reverse). They were
   not tested on a Nexus database for this book. If you go this way, create the target schema with the installer
   first (so column types, indexes and table options are the ones the application expects), then load **data only**
   into it, then check row counts table by table and run `PRAGMA integrity_check`, `CHECK TABLE` or the PostgreSQL
   equivalent.

Whichever way you choose:

- Reset sequences and auto-increment counters on the target to above the highest imported id (PostgreSQL in
  particular does not do this when rows are loaded with explicit ids).
- Carry the `nglayouts_*` tables and `nglayouts_migration_versions` across, or Layouts migrations try to run again.
- Expect differences in sorting and comparison: MySQL's `utf8mb4_unicode_520_ci` compares case-insensitively,
  PostgreSQL and SQLite compare case-sensitively by default.
- Change `DATABASE_URL` (or the `DATABASE_*` parameters), clear the cache, and on 1.0.0.x to 1.2.0.x remember that
  the legacy kernel follows automatically through the bridge.
- Keep the old database untouched until the new site has been checked page by page.

---

## 7.11 Checklist

- [ ] The engine is chosen for the site's editing load; SQLite only where one writer at a time is enough.
- [ ] The database exists with `utf8mb4` / `utf8mb4_unicode_520_ci` (MySQL, MariaDB) or `UTF8` and an owning user
      (PostgreSQL).
- [ ] The connection is set in `.env.local` or the environment (1.1.0.x and later) or in `app/config/parameters.yml`
      (1.0.0.x), never in a committed file, and the password is percent-encoded if needed.
- [ ] `serverVersion` (or `DATABASE_VERSION` on 1.0.0.x) matches the real server.
- [ ] `php bin/console list | grep :install` shows which install command this installation registers.
- [ ] After installing, the `nglayouts_*` tables exist and `nglayouts_migration_versions` is filled.
- [ ] `schema_filter: ~^(?!nglayouts_)~` is still in `doctrine.yaml`.
- [ ] SQLite: the file and `var/` are writable by every process that touches them, and `APP_ENV` points the URL at
      the file you mean.
- [ ] Backups of the database and the storage directory run on a schedule and a restore has been tried once.
- [ ] Hand-written SQL has been checked against the `ibexa_*` table and column names on 1.3.0.x.

---

## 7.12 References

In this repository (`master`):

- [doc/INSTALL.md](../INSTALL.md): the short installation guide (in the releases `1.0.0.9` and `v2.5.0.6`,
  `doc/INSTALL.md` is the line's own guide, with sections on creating the database and SQLite)
- [app/config/config.yml](../../app/config/config.yml), [app/config/default_parameters.yml](../../app/config/default_parameters.yml),
  [app/config/parameters.yml.dist](../../app/config/parameters.yml.dist): the Symfony 3.4 connection
- [app/config/env/generic.php](../../app/config/env/generic.php) (DFS variables),
  [app/config/env/platformsh.php](../../app/config/env/platformsh.php) (Platform.sh relationships)
- [compose.yaml](../../compose.yaml) and [doc/docker/db-postgresql.yml](../docker/db-postgresql.yml)
- [doc/cjw/PACKAGE_CHANGELOG.md](../cjw/PACKAGE_CHANGELOG.md): the Netgen Layouts migration command

On the other branches (GitHub):

- 1.0.0.x: [app/config/config.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.0.0.x/app/config/config.yml),
  [app/config/default_parameters.yml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.0.0.x/app/config/default_parameters.yml)
- 1.1.0.x: [.env](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/.env),
  [config/packages/doctrine.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/packages/doctrine.yaml),
  [config/packages/ez_doctrine_schema.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/packages/ez_doctrine_schema.yaml),
  [doc/sevenx/INSTALL.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/doc/sevenx/INSTALL.md)
- 1.2.0.x: [config/packages/doctrine.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/config/packages/doctrine.yaml),
  [config/packages/ibexa_doctrine_schema.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/config/packages/ibexa_doctrine_schema.yaml),
  [doc/sevenx/INSTALL.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/doc/sevenx/INSTALL.md)
- 1.3.0.x: [.env](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/.env),
  [config/packages/doctrine.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/doctrine.yaml),
  [config/packages/doctrine_migrations.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/doctrine_migrations.yaml),
  [config/packages/messenger.yaml](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/messenger.yaml),
  [doc/sevenx/INSTALL.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/doc/sevenx/INSTALL.md),
  [doc/netgen/IBEXA_MIGRATIONS.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/doc/netgen/IBEXA_MIGRATIONS.md)

The 1.3.0.x core package: [se7enxweb/exponential-platform-dxp-core](https://packagist.org/packages/se7enxweb/exponential-platform-dxp-core)
(source [se7enxweb/core](https://github.com/se7enxweb/core); the installer is
`src/bundle/RepositoryInstaller/Installer/CoreInstaller.php`).

Other chapters: [2. Requirements](02-requirements.md), [4. Installing](04-installing.md),
[5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md), [6. Serving the site](06-serving-the-site.md),
[8. Configuration](08-configuration.md), [10. Operations](10-operations.md).

The Exponential 6 book: [contents](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md) and
[chapter 9, Databases](https://github.com/se7enxweb/exponential/blob/main/doc/install/09-databases.md), which covers
SQLite as a production database for the legacy kernel (WAL, queued writes, backups, mixed users) in depth.

External:

- Doctrine DBAL: [connecting using a URL](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url);
  Symfony: [Databases and the Doctrine ORM](https://symfony.com/doc/current/doctrine.html),
  [Doctrine configuration reference](https://symfony.com/doc/current/reference/configuration/doctrine.html),
  [DoctrineMigrationsBundle](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html),
  [environment variables and .env files](https://symfony.com/doc/current/configuration.html#configuration-based-on-environment-variables),
  [secrets](https://symfony.com/doc/current/configuration/secrets.html)
- PHP: [PDO](https://www.php.net/manual/en/book.pdo.php), [PDO_MYSQL](https://www.php.net/manual/en/ref.pdo-mysql.php),
  [PDO_PGSQL](https://www.php.net/manual/en/ref.pdo-pgsql.php), [PDO_SQLITE](https://www.php.net/manual/en/ref.pdo-sqlite.php)
- Ibexa documentation (upstream of 1.2.0.x and 1.3.0.x): <https://doc.ibexa.co/en/5.0/>, including its update notes from
  4.6 to 5.0 for the table rename
- Netgen documentation (Layouts, Site API, Tags): <https://docs.netgen.io/en/latest/>
- MySQL: [the utf8mb4 character set](https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb4.html),
  [mysqldump](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html),
  [CREATE USER](https://dev.mysql.com/doc/refman/8.0/en/create-user.html); MariaDB:
  [mariadb-dump](https://mariadb.com/docs/server/clients-and-utilities/backup-restore-and-import-clients/mariadb-dump)
- PostgreSQL: [CREATE DATABASE](https://www.postgresql.org/docs/current/sql-createdatabase.html),
  [pg_dump](https://www.postgresql.org/docs/current/app-pgdump.html),
  [pg_restore](https://www.postgresql.org/docs/current/app-pgrestore.html)
- SQLite: [write-ahead logging](https://www.sqlite.org/wal.html), [the command-line shell](https://www.sqlite.org/cli.html),
  [backup API](https://www.sqlite.org/backup.html), [file locking and concurrency](https://www.sqlite.org/lockingv3.html),
  [when to use SQLite](https://www.sqlite.org/whentouse.html)

[Previous: 6. Serving the site](06-serving-the-site.md) · [Next: 8. Configuration](08-configuration.md) ·
[Contents](README.md)
