# 13. Troubleshooting

This chapter lists what goes wrong with an Exponential Platform Nexus installation, ordered by the stage at which you
meet it: getting the dependencies, building the front end, installing the database, the first requests, Netgen
Layouts, caches, permissions, databases, the legacy kernel, and signing in. Every entry has the same shape: the
**symptom** as you see it, the **cause**, the **fix**, and the line or lines it applies to. Where a cause was read
from the code of a line rather than reproduced, the entry says so.

[Previous: 12. Migrating into Nexus](12-migrating-into.md) · [Next: 14. Security hardening](14-security-hardening.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [First steps for any problem](#131-first-steps-for-any-problem)
2. [Composer and dependencies](#132-composer-and-dependencies)
3. [Front-end build](#133-front-end-build)
4. [Installing the database](#134-installing-the-database)
5. [The first requests](#135-the-first-requests)
6. [Netgen Layouts](#136-netgen-layouts)
7. [Caches](#137-caches)
8. [Files and permissions](#138-files-and-permissions)
9. [Databases](#139-databases)
10. [The legacy kernel (1.0.0.x to 1.2.0.x)](#1310-the-legacy-kernel-100x-to-120x)
11. [Signing in and users](#1311-signing-in-and-users)
12. [Search and images](#1312-search-and-images)
13. [Asking for help](#1313-asking-for-help)
14. [References](#1314-references)

---

## 13.1 First steps for any problem

Before searching this chapter, collect three facts.

**Which line, which release.**

```bash
git describe --tags                       # the release the checkout is on
php bin/console --version                 # Symfony version, kernel, environment, debug flag
php -v                                    # the CLI PHP; the web server's PHP may differ
```

**The log.** The real error is almost always in the application log, not in the browser:

| Line | Log directory | Production log |
|---|---|---|
| 1.0.0.x | `var/logs/` | `var/logs/prod.log` |
| 1.1.0.x to 1.3.0.x | `var/log/` | `var/log/prod.log` |

```bash
tail -n 100 var/log/prod.log          # var/logs/prod.log on 1.0.0.x
```

Then the web server's error log and the PHP-FPM log of the pool that serves the site.

**The environment the web request runs in.** On 1.1.0.x and later the environment comes from `APP_ENV` in `.env`,
`.env.local` and the real environment; the shipped `.env` says `APP_ENV=dev`. On 1.0.0.x it comes from
`SYMFONY_ENV` (default `prod`), and `web/app.php` switches to `dev` whenever the request's `Host` contains `dev.`
([14.4](14-security-hardening.md#144-debug-mode-and-the-environment)). A page that behaves differently from the
console usually runs in another environment.

## 13.2 Composer and dependencies

### `Your requirements could not be resolved` because of the PHP version

- **Cause:** each line has its own PHP floor: 1.0.0.x in practice 8.1 (the legacy kernel requires `^8.1`), 1.1.0.x
  8.0, 1.2.0.x 8.2, 1.3.0.x 8.4. Composer checks the PHP that runs Composer, which may not be the PHP of the web server.
- **Fix:** run Composer with the same PHP binary as the site (`/opt/plesk/php/8.4/bin/php /usr/local/bin/composer install`
  on Plesk, for example). Use `--ignore-platform-reqs` only to get past a package that declares a narrower range than
  it really supports, never to install a line on a PHP version below its floor: the code then fails at run time.

### `Class "..." not found` after `composer update`

- **Cause:** a stale class map or a stale container in `var/cache/`.
- **Fix:**

  ```bash
  composer dump-autoload -o
  php bin/console cache:clear
  ```

### Upstream package installed next to its fork (1.3.0.x)

- **Symptom:** HTTP 500 with `Call to undefined method Netgen\Layouts\...\Parameter::isEmpty()`, or RichText fails
  with an XSL stylesheet that cannot be found.
- **Cause:** Composer installed `netgen/layouts-core` or `ibexa/fieldtype-richtext` instead of the forks
  `se7enxweb/layouts-core` and `se7enxweb/fieldtype-richtext` that the metapackage `se7enxweb/exponential-platform-dxp`
  requires.
- **Fix:**

  ```bash
  composer show se7enxweb/layouts-core netgen/layouts-core
  composer why netgen/layouts-core
  composer update se7enxweb/exponential-platform-dxp se7enxweb/layouts-core --with-dependencies
  php bin/console cache:clear
  ```

  Do not require the forks directly in the project; the metapackage pins them.

### The legacy kernel directory lost your changes (1.0.0.x, 1.1.0.x)

- **Symptom:** after `composer install` or `update`, legacy extensions, siteaccess settings or the storage link are
  missing from `ezpublish_legacy/`; images 404, the legacy admin shows the wrong design.
- **Cause:** `ezpublish_legacy/` is installed by Composer (`se7enxweb/exponential-legacy-installer`) and replaced on
  every run. Anything placed inside it by hand is gone.
- **Fix:** keep your legacy files in the project (on 1.0.0.x under `src/AppBundle/ezpublish_legacy/`) and let the
  Composer scripts link them: they run `ezpublish:legacybundles:install_extensions`, `ngsite:symlink:legacy` and
  `ezpublish:legacy:script bin/php/ezpgenerateautoloads.php` after every install and update. Re-run them by hand if a
  script step failed:

  ```bash
  php bin/console ezpublish:legacybundles:install_extensions --relative
  php bin/console ngsite:symlink:legacy
  php bin/console ezpublish:legacy:script bin/php/ezpgenerateautoloads.php
  ```

  The storage directory link (`ezpublish_legacy/var/site/storage` pointing at
  `src/AppBundle/ezpublish_legacy/var/site/storage`) is not made by those scripts; recreate it as
  [`doc/INSTALL.md`](../INSTALL.md) shows.

## 13.3 Front-end build

### `ERR_OSSL_EVP_UNSUPPORTED` during `encore` (1.0.0.x)

- **Cause:** 1.0.0.x builds with Webpack 4 (`@symfony/webpack-encore ^0.27.0`), whose hashing uses an algorithm
  OpenSSL 3 disables by default. Node.js 17 and later ship OpenSSL 3.
- **Fix:** use the npm scripts, which set `NODE_OPTIONS=--openssl-legacy-provider` since commit `0e55f2c`
  (`npm run build:prod`, `npm run build:dev`, `npm run watch`). If you call `encore` directly, set the variable yourself:

  ```bash
  NODE_OPTIONS=--openssl-legacy-provider npx encore production
  ```

### `--openssl-legacy-provider is not allowed in NODE_OPTIONS` (1.0.0.x)

- **Cause:** the opposite case: Node.js 16 or older does not know the option, and refuses to start with it.
- **Fix:** use a current Node.js (the project was last built on Node 25, according to the commit message of
  `0e55f2c`). Older Node versions would need the variable removed from `package.json`, which is not the way forward.

### `EntrypointNotFoundException: Could not find the entry "photoswipe-init"` (1.0.0.x)

- **Cause:** the templates ask Encore for the entries `app` and `photoswipe-init` (both defined in
  `webpack.config.default.js`), but `web/assets/app/build/entrypoints.json` does not list them. The configuration
  calls `cleanupOutputBeforeBuild()`, so a build that fails half way leaves an emptied output directory behind; a
  checkout that deleted the committed `web/assets/app/build/` has the same effect.
- **Fix:** run a production build that finishes without errors, and check the file:

  ```bash
  npm install
  npm run build:prod
  grep -c photoswipe-init web/assets/app/build/entrypoints.json
  ```

  Development builds go to `web/assets/app/build_dev/`; the `prod` environment reads `build/`.

### `The engine "node" is incompatible with this module` (1.1.0.x to 1.3.0.x)

- **Cause:** `package.json` declares `engines`: `^18 || ^20` (1.1.0.x), `^18` (1.2.0.x), `^22` (1.3.0.x), and Yarn
  enforces it.
- **Fix:** use the version in `.nvmrc`:

  ```bash
  nvm install && nvm use       # reads .nvmrc: v18 on 1.1.0.x and 1.2.0.x, v22 on 1.3.0.x
  node --version
  ```

### `Cannot find module '@ibexa/frontend-config'` (1.3.0.x)

- **Cause:** `node_modules/` is missing or was installed for another line; the admin build of 1.3.0.x takes its
  configuration from that package (`yarn ibexa:build` runs `encore production --config ./node_modules/@ibexa/frontend-config/...`).
- **Fix:** `yarn install`, then `yarn ibexa:build`. If SASS or module errors remain, publish the bundle assets first
  (`php bin/console assets:install --symlink --relative public`), because the admin build reads files the bundles
  publish under `var/encore/`.

### Sass deprecation warnings during the build

- **Cause:** the themes use Sass features that newer Dart Sass versions deprecate (for example `[function-units]`).
- **Fix:** none needed; they are warnings and the build completes. Treat them as a to-do for the theme, not as an error.

## 13.4 Installing the database

### The installer type is not found

- **Symptom:** `Unknown installer type` or the command lists other types.
- **Cause:** each line registers different types. The 2.5 generation: `cjw-exponential-media` on `master` (project
  type, data from `se7enxweb/cjw-exponential-media-site-data`), `exponential-cjw` on the `1.0.0.x` branch,
  `exponential-oss` and `clean` from the kernel fork on both, and the `netgen-media*` types of
  `netgen/site-installer-bundle`, which need the data package `netgen/media-site-data` (required on the `1.0.0.x`
  branch, only suggested on `master`). 1.1.0.x and later: `exponential-media` through `exponential:install`;
  1.3.0.x also `exponential-oss` and `ibexa-oss`.
- **Fix:** on `master` run `php bin/console ezplatform:install cjw-exponential-media`; on the later lines
  `php bin/console exponential:install exponential-media`. See [chapter 4](04-installing.md) for every line.

### `no such table: ibexa_section` (1.3.0.x, SQLite)

- **Cause:** the SQLite file exists but the installer never ran against it (the file is created empty by the first
  connection).
- **Fix:** `php bin/console exponential:install exponential-media --no-interaction`, then make the file writable for
  the web server's user ([13.8](#138-files-and-permissions)).

### `Migration can only be executed safely on MySQL.`

- **Cause:** the Netgen Layouts schema migrations abort on every platform but MySQL (checked at the top of each
  migration class).
- **Fix:** on PostgreSQL and SQLite, the Layouts tables come from the installer of the line; schema changes have to be
  applied by hand. See [chapter 11.7](11-upgrading-between-lines.md#117-netgen-layouts-across-the-lines).

## 13.5 The first requests

### HTTP 500 and nothing useful in the browser

- **Cause:** production mode hides the error.
- **Fix:** read `var/log/prod.log` (`var/logs/prod.log` on 1.0.0.x). Do not switch production to `dev` to read an
  error; reproduce it on a staging copy instead ([chapter 14](14-security-hardening.md)).

### 404 for every page, or the front controller downloads as a file

- **Cause:** the web server's document root points at the project root instead of `public/` (1.1.0.x and later) or
  `web/` (1.0.0.x), or the rewrite rules are missing.
- **Fix:** see [chapter 6](06-serving-the-site.md). On 1.0.0.x the front controller is `web/app.php`; the
  `public/index.php` file on that branch uses the Symfony Runtime, which the Symfony 3.4 line does not install, so
  serving `public/` on 1.0.0.x fails.

### Absolute URLs and redirects use `http://` behind a TLS proxy

- **Cause:** Symfony does not trust the proxy's `X-Forwarded-Proto`. The `TRUSTED_PROXIES` line in the `.env` files
  of 1.1.0.x to 1.3.0.x is not read by any configuration of those lines.
- **Fix:** configure `framework.trusted_proxies` and `trusted_headers`, and on 1.0.0.x to 1.2.0.x also the legacy
  kernel's `TrustedProxies[]`. See [14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies).

## 13.6 Netgen Layouts

### `Container extension "netgen_layouts" is not registered` (1.3.0.x)

- **Cause:** when Composer switches between `netgen/layouts-core` and `se7enxweb/layouts-core`, Symfony Flex runs the
  upstream package's unconfigure recipe, which removes `NetgenLayoutsBundle` and `NetgenLayoutsAdminBundle` from
  `config/bundles.php` and deletes `config/routes/netgen_layouts.yaml`.
- **Fix:**

  ```bash
  git diff config/bundles.php config/routes/
  git checkout HEAD -- config/bundles.php config/routes/netgen_layouts.yaml
  php bin/console cache:clear
  ```

  Check `git diff config/` after every Composer run that touches a `layouts-*` package.

### `Route "nglayouts_app" not found` (1.3.0.x)

- **Cause:** the same unconfigure removed only the route file.
- **Fix:** restore `config/routes/netgen_layouts.yaml`, whose content is:

  ```yaml
  netgen_layouts:
      resource: "@NetgenLayoutsBundle/Resources/config/routing.yaml"
      prefix: "%netgen_layouts.route_prefix%"
  ```

  `netgen_layouts.route_prefix` defaults to `/nglayouts`.

### A layout-managed page renders empty or fails after moving to 1.2.0.x or later

- **Cause:** the Layouts tables still carry the identifiers of the eZ Platform integration (`ezcontent_field`,
  `ezlocation`, `ez_subtree`, ...), which the `layouts-ibexa` packages do not register.
- **Fix:** run the identifier SQL of [chapter 11.5](11-upgrading-between-lines.md#115-from-110x-to-120x-platform-33-to-ibexa-oss-46),
  then list what is left:

  ```sql
  SELECT DISTINCT type FROM nglayouts_rule_target;
  SELECT DISTINCT type FROM nglayouts_collection_query;
  SELECT DISTINCT value_type FROM nglayouts_collection_item;
  ```

### Imported rules match nothing

- **Cause:** `nglayouts:import` maps location targets and collection items by remote id; a remote id that does not
  exist in the receiving repository becomes `0`.
- **Fix:** make sure the content exists with the same remote ids before you import, or edit the rule targets in the
  Layouts admin after the import. See [chapter 12.11](12-migrating-into.md#1211-moving-layouts-between-installations).

### Editors cannot open the Layouts editor

- **Cause:** access to Layouts is checked against repository policies of the module `nglayouts`: `nglayouts/admin`,
  `nglayouts/editor` and `nglayouts/api` stand for the roles `ROLE_NGLAYOUTS_ADMIN`, `ROLE_NGLAYOUTS_EDITOR` and
  `ROLE_NGLAYOUTS_API`.
- **Fix:** give the editors' role the `nglayouts/editor` policy (and `nglayouts/api`, which the editor's JavaScript
  calls); `nglayouts/admin` only to those who manage layout types and rules.

## 13.7 Caches

### A change does not show

- **Configuration, templates, services:** `php bin/console cache:clear` (add `--env=prod` where the default is `dev`).
- **Persistence cache** (content, locations, user data) on 1.3.0.x: from the 5.0.7 kernel `cache:clear` no longer
  empties it. Clear the pool named by `CACHE_POOL`:

  ```bash
  php bin/console cache:pool:clear cache.tagaware.filesystem
  ```

- **HTTP cache:** purge the paths or tags that changed:

  ```bash
  php bin/console fos:httpcache:invalidate:path /some/page --env=prod
  php bin/console fos:httpcache:invalidate:tag <tag> --env=prod
  ```

  The tag names (location, content, content type tags) are set by the line's HTTP cache package; read them from the
  `xkey` response header of the page you want to purge, as the application sends it (ask the application directly,
  not through Varnish, whose VCL may remove the header).

  There is no `--all` option on `invalidate:path`. To empty the Symfony proxy completely, delete its store under
  `var/cache/<env>/http_cache/`; with Varnish, ban or restart.
- **Legacy kernel caches** (1.0.0.x to 1.2.0.x):
  `php bin/console ezpublish:legacy:script bin/php/ezcache.php --clear-all`.
- **Opcode cache:** after a deployment PHP-FPM may still run the old code; reload the pool.

### `cache:clear` fails with permission denied, or the site fails after a console command

- **Cause:** the console ran as root (or another user) and wrote `var/cache/` files that the web server's user cannot
  read or replace. The mirror case also exists: the web server wrote files the deploy user cannot delete.
- **Fix:** run the console as the web server's user, and correct ownership once:

  ```bash
  sudo -u www-data php bin/console cache:clear --env=prod
  sudo chown -R www-data:www-data var/cache var/log
  ```

  Do not run the console with `sudo` as root as a habit; that is what causes the problem.

## 13.8 Files and permissions

### `Unable to create the store directory` or `Failed to write cache file`

- **Cause:** `var/` (and on 1.0.0.x `web/var` and `ezpublish_legacy/var`) is not writable for the web server.
- **Fix:** make exactly those directories writable, not the whole tree:

  ```bash
  sudo setfacl -R  -m u:www-data:rwX -m u:$(whoami):rwX var public/var
  sudo setfacl -dR -m u:www-data:rwX -m u:$(whoami):rwX var public/var
  # 1.0.0.x: var web/var ezpublish_legacy/var
  ```

  [Chapter 6](06-serving-the-site.md) lists the writable set per line.

### `attempt to write a readonly database` (SQLite)

- **Cause:** SQLite needs write access to the database file **and** to the directory that contains it, because it
  creates its journal and WAL files next to the database.
- **Fix:** give the web server's user write access to the file and to `var/`:

  ```bash
  sudo chown www-data:www-data var/data_prod.db
  sudo chmod 660 var/data_prod.db
  sudo setfacl -m u:www-data:rwX var
  ```

  The file name is whatever `DATABASE_URL` names: the examples in `.env` use `var/data.db` (1.1.0.x, 1.2.0.x) and
  `var/data_%kernel.environment%.db` (1.3.0.x), which is `var/data_prod.db` in production.

### Images do not display

- **Cause:** the storage directory is not where the IO configuration expects it, a link is missing (1.0.0.x), or the
  web server does not serve `var/.../storage/images/` directly.
- **Fix:** check that the original exists on disk, that the rewrite rules pass `/var/<site>/storage/images/` through
  ([chapter 6](06-serving-the-site.md)), and that the web server can read the directory. On 1.0.0.x check the storage
  link of [13.2](#132-composer-and-dependencies).

## 13.9 Databases

### Connection refused or access denied

- **Fix:** test the same credentials outside the application, with the same host and port:

  ```bash
  mysql -h 127.0.0.1 -u USER -p DATABASE -e 'SELECT 1'
  psql "postgresql://USER@127.0.0.1:5432/DATABASE" -c 'SELECT 1'
  ```

  On 1.1.0.x and later the value used is the final `DATABASE_URL` after `.env`, `.env.local`, `.env.<env>.local` and
  the real environment; `php bin/console debug:dotenv` (Symfony 5.4 and later) shows which file won.

### `could not find driver`

- **Cause:** the PDO extension of the engine (`pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`) is missing in the PHP that runs
  the request. CLI and FPM often load different `php.ini` files.
- **Fix:** `php -m | grep -i pdo` for the CLI; a `phpinfo()` page on a staging host, or the FPM pool's configuration,
  for the web side.

### `permission denied for schema public` (PostgreSQL 15 and later)

- **Cause:** since PostgreSQL 15 ordinary users cannot create tables in the `public` schema of a database they do not
  own. `GRANT ALL PRIVILEGES ON DATABASE` is not enough.
- **Fix:** make the application user the owner of the database:

  ```sql
  CREATE USER nexus WITH PASSWORD 'change-me';
  CREATE DATABASE nexus OWNER nexus ENCODING 'UTF8';
  ```

  For an existing database: `ALTER DATABASE nexus OWNER TO nexus;` and `ALTER SCHEMA public OWNER TO nexus;`. See
  [chapter 7](07-databases.md).

### `GRANT ... IDENTIFIED BY` fails on MySQL 8

- **Cause:** MySQL 8.0 removed the combined form.
- **Fix:** create the user first, then grant:

  ```sql
  CREATE USER 'nexus'@'localhost' IDENTIFIED BY 'change-me';
  GRANT ALL PRIVILEGES ON nexus.* TO 'nexus'@'localhost';
  ```

### Index too long, or `Specified key was too long` on import

- **Cause:** a database created with another character set than `utf8mb4`, or an old MySQL with short index
  prefixes.
- **Fix:** create the database with `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci` (the collation the
  lines' configuration names in `DATABASE_COLLATION`) before importing.

### A keyword field cannot be saved from the legacy admin (MySQL, 1.1.0.x and 1.2.0.x)

- **Cause:** the 2.5 to 3.0 script adds `ezkeyword_attribute_link.version` as `NOT NULL` without a default; the legacy
  kernel inserts keyword links without the column.
- **Fix:** `ALTER TABLE ezkeyword_attribute_link MODIFY version INT(11) NOT NULL DEFAULT 0;` (chapter
  [11.4](11-upgrading-between-lines.md#114-from-100x-to-110x-platform-25-to-33-symfony-34-to-54)).

## 13.10 The legacy kernel (1.0.0.x to 1.2.0.x)

### `There are no commands defined in the "ezpublish:legacy" namespace`

- **Cause:** the legacy bundle is not registered for the environment, or (on 1.2.0.x) the container cache predates
  its registration. All three legacy-capable lines register `EzPublishLegacyBundle`.
- **Fix:** `php bin/console cache:clear`, then `php bin/console list ezpublish`. On 1.1.0.x and 1.2.0.x the bridge's
  primary names are `exponential:legacy:*`; the `ezpublish:legacy:*` names are aliases.

### The legacy admin shows a blank page or the wrong design

- **Cause:** the legacy siteaccess settings were not linked into `ezpublish_legacy/settings/siteaccess/`, or the
  legacy autoload array is stale.
- **Fix:** re-run the scripts of [13.2](#132-composer-and-dependencies), then clear the legacy caches.

### Legacy cron jobs do not run

- **Cause:** the legacy cron runs through the bridge, not through `ibexa:cron:run`. The 1.0.0.x example `ngsite.cron`
  calls `bin/console ezpublish:legacy:script runcronjobs.php` with the siteaccess `ngadminui` and needs
  `EZPUBLISHROOT` set to the project directory.
- **Fix:** install a crontab built from `ngsite.cron` with the real path, as the web server's user.

## 13.11 Signing in and users

### A user cannot sign in after an upgrade to 1.1.0.x or later

- **Cause:** the password hash is of a type removed in 3.0 (MD5 types 1 to 3, plain text type 5).
- **Fix:** list the affected users and have them reset their password:

  ```sql
  SELECT contentobject_id, login, password_hash_type FROM ezuser WHERE password_hash_type IN (1, 2, 3, 5);
  ```

  `php bin/console exponential:user:validate-password-hashes` reports the same on the forks that have the command.

### The admin interface redirects back to the login form

- **Cause:** the session cookie is not sent back: a `cookie_secure` session over plain HTTP, a proxy that is not
  trusted (the application thinks the request is HTTP), or a session directory that is not writable.
- **Fix:** serve the admin over HTTPS, configure trusted proxies ([chapter 14](14-security-hardening.md)), and check
  `SESSION_SAVE_PATH` (1.1.0.x and later: `%kernel.project_dir%/var/sessions/%kernel.environment%`) is writable.

### JWT errors on the REST API (1.1.0.x to 1.3.0.x)

- **Cause:** the key pair under `config/jwt/` is missing, or was created with another passphrase than `JWT_PASSPHRASE`.
- **Fix:** `php bin/console lexik:jwt:generate-keypair --overwrite`, with `JWT_PASSPHRASE` set to your own value first.

## 13.12 Search and images

### Search results are outdated or empty

- **Fix:** reindex. 1.0.0.x: `php bin/console ezplatform:reindex`; 1.1.0.x and later:
  `php bin/console exponential:reindex` (with `--iteration-count=100` on large repositories). After switching
  `SEARCH_ENGINE` to `solr`, create the Solr core first; "did you mean" suggestions need the Solr spellcheck
  configuration ([SEARCH_SUGGESTIONS](../netgen/SEARCH_SUGGESTIONS.md)).

### Image variations are missing or stale

- **Fix:** remove the generated variations; they are generated again on the next request:

  ```bash
  php bin/console liip:imagine:cache:remove
  php bin/console liip:imagine:cache:remove --filter=i320
  ```

  On 1.0.0.x a demo with many large images can pre-generate the common variations with
  `php bin/console ngsite:content:generate-image-variations --variations=i30,i160,i320,i480`.

## 13.13 Asking for help

When you report a problem, include: the line and release (`git describe --tags`), PHP version of CLI and web, the
database engine and version, the exact error from the log with its stack trace, and the command or URL that triggers
it. Issues go to the [issue tracker](https://github.com/se7enxweb/exponential-platform-nexus/issues); security
problems are reported privately as [SECURITY.md](../../SECURITY.md) says, never in a public issue.

## 13.14 References

In this repository:

- `web/app.php`, `webpack.config.default.js`, `package.json`, `ngsite.cron` (1.0.0.x, `master`)
- `doc/sevenx/INSTALL.md` on `1.1.0.x`, `1.2.0.x`, `1.3.0.x` (`git show 1.3.0.x:doc/sevenx/INSTALL.md`), section
  "Troubleshooting"
- [Chapter 6: Serving the site](06-serving-the-site.md), [chapter 7: Databases](07-databases.md),
  [chapter 9: Front end and themes](09-frontend-and-themes.md), [chapter 10: Operations](10-operations.md)

Exponential:

- [Exponential book, chapter 12: Troubleshooting](https://github.com/se7enxweb/exponential/blob/main/doc/install/12-troubleshooting.md)
  for the legacy kernel itself
- [Exponential book, chapter 16.7: Common issues](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#167-common-issues)

External:

- [Symfony: How to configure permissions](https://symfony.com/doc/current/setup/file_permissions.html)
- [Symfony: Configuring Symfony to work behind a proxy](https://symfony.com/doc/current/deployment/proxies.html)
- [Netgen Layouts documentation](https://docs.netgen.io/projects/layouts/en/latest/)
- [PostgreSQL 15 release notes](https://www.postgresql.org/docs/release/15.0/) (the `public` schema change)
- [Ibexa: Common migration issues](https://doc.ibexa.co/en/5.0/update_and_migration/migrate_to_ibexa_dxp/common_issues/)
