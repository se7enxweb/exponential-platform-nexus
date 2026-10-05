# 2. Requirements

This chapter lists what a machine needs before Exponential Platform Nexus can be installed on it, line by line: the
PHP versions each line accepts and the ones it was tested on, the PHP extensions and `php.ini` settings, the database
servers, Composer, Node.js and Yarn for the front-end build, a web server, the optional services (Solr, Varnish,
Redis, ImageMagick), notes per operating system, and what the installation needs in memory and disk. Everything is
read from the `composer.json`, `package.json`, `.nvmrc` and configuration of each branch and tag; where the
repository's own guides say something different, the chapter follows the code and says so.

[Contents](README.md) · Previous: [1. Introduction](01-introduction.md) · Next: [3. Getting the code](03-getting-the-code.md)

## 2.1 The checklist

| Requirement | 2.5 (`master`) and 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|
| PHP | 8.1 or later (the legacy kernel's minimum; `composer.json` alone accepts 7.1.3 to 8.6); Composer run with `--ignore-platform-reqs` | 8.0 or later; 8.5 recommended | 8.2 or later; 8.5 recommended | 8.4 or later; 8.5 recommended |
| PHP extensions | see [2.3](#23-php-extensions); APCu is used by the shipped configuration | `gd`, `curl`, `json`, `xsl`, `xml`, `intl`, `mbstring`, `ctype`, `iconv` and a PDO driver | as 1.1.0.x | as 1.1.0.x |
| Database | MySQL 5.7+ or MariaDB 10.2+ (utf8mb4); PostgreSQL per the guide; SQLite 3 on 1.0.0.x only | MySQL 8.0+, MariaDB 10.3+, PostgreSQL 14+ or SQLite 3.35+ | as 1.1.0.x | as 1.1.0.x |
| Composer | 2.x | 2.x | 2.x | 2.x |
| Node.js | `master`: 22 (`.nvmrc`); `1.0.0.x`: 20 (`.nvmrc`, the guide); both builds need the OpenSSL legacy provider on Node.js 17 and later | 18 or 20 (`engines`), `.nvmrc`: 18 | 18 (`engines`, `.nvmrc`) | 22 (`engines`, `.nvmrc`) |
| Yarn | 1.x, needed by `composer install` itself | 1.22 | 1.22 | 1.22 |
| Web server | Apache 2.4 or nginx with PHP-FPM; PHP's built-in server for development | Apache 2.4 or nginx 1.18+ with PHP-FPM; Symfony CLI for development | as 1.1.0.x | as 1.1.0.x |
| Document root | `web/` | `public/` | `public/` | `public/` |
| Git | for `--keep-vcs` and for installing from a clone | as 2.5 | as 2.5 | as 2.5 |

The sections below explain each row.

## 2.2 PHP

### Versions

| Line | `composer.json` | What the line's documentation says |
|---|---|---|
| 2.5 (`v2.5.0.3` and later, `master`) | `^7.1.3 \|\| ^7.2 \|\| ^7.4 \|\| ^8.0 \|\| ^8.1 \|\| ^8.2 \|\| ^8.3 \|\| ^8.4 \|\| ^8.5 \|\| ^8.6` | README of `master`: "PHP 8.1 or newer" (the README of `v2.5.0.6`: "supports PHP 8.3 -> 8.5"); the guide of `v2.5.0.6`: "PHP 8.1+ (8.5 branch strongly recommended)" |
| 2.5 (`v2.5.0.0`, `v2.5.0.1`) | `^7.1.3 \|\| ^8.1 \|\| ^8.2` | as above |
| 1.0.0.x | as 2.5 (`v1.0.0.0.3`: the same list without `^8.0`) | "The latest version of the 8.5 branch is strongly recommended" |
| the legacy kernel of the 2.5 generation (`se7enxweb/exponential` 6.0) | `^8.1 \|\| ... \|\| ^8.8` | the effective minimum of the line: Composer would refuse it on PHP 8.0, were the check not switched off |
| 1.1.0.x | `^8.0` | "PHP 8.0+ (PHP 8.5 strongly recommended)"; tested on PHP 8.5.5 |
| 1.2.0.x | `>=8.2` | "PHP 8.2+ (PHP 8.5 strongly recommended)"; tested on PHP 8.5.5 |
| 1.3.0.x | `>=8.4` | "PHP 8.4 minimum is non-negotiable"; tested on PHP 8.5 |

Three things follow from the table:

1. **The 2.5 generation needs `--ignore-platform-reqs`.** Its `composer.json` accepts current PHP, but the install
   guides of the line (`doc/INSTALL.md` in the releases `1.0.0.9` and `v2.5.0.6`) say the flag is still required "due to ongoing package definition updates across
   repositories": some dependencies of the Symfony 3.4 stack declare older PHP limits than the versions 7x has made them
   work on. The flag makes Composer skip the PHP and extension checks entirely, so you have to check them yourself
   ([2.3](#23-php-extensions)).
2. **Use the newest PHP the line accepts.** 7x tests the 1.1.0.x, 1.2.0.x and 1.3.0.x lines on PHP 8.5 and runs the
   reference installation of 1.3.0.x on PHP 8.5. On the 2.5 generation the floor is PHP 8.1, set by the legacy
   kernel; because `--ignore-platform-reqs` hides that check, nothing stops an installation on PHP 8.0, which the
   kernel does not support. Prefer 8.3 to 8.5, the versions the 7x READMEs name.
3. **1.3.0.x refuses anything older than 8.4.** Its 7x forks (`se7enxweb/layouts-core`, `se7enxweb/fieldtype-richtext`,
   `se7enxweb/site-bundle`) exist because upstream packages broke under PHP 8.4 and Twig 3.24, and they rely on PHP 8.4
   language features.

See [PHP supported versions](https://www.php.net/supported-versions.php) for the end of life of each PHP release.

### Command line and web PHP

The console (`php bin/console`), Composer and the web server must run the **same PHP version with the same
extensions**. A site whose PHP-FPM runs 8.5 while the command line runs 8.2 compiles its container with one set of
classes and serves it with another. Check both:

```bash
php -v                        # the command line PHP
php -m                        # its extensions
php bin/console about         # Symfony's view: PHP version, environment, cache and log directories
```

On a server with several PHP builds (Plesk, Remi, Ondřej Surý's packages), call the matching binary explicitly, for
example `/opt/plesk/php/8.5/bin/php bin/console`.

### `php.ini` settings

| Setting | Value | Why |
|---|---|---|
| `memory_limit` | 2.5 generation: at least 464M (the install guides released with `1.0.0.9` and `v2.5.0.6`); 1.1.0.x to 1.3.0.x: at least 256M, 512M recommended | the container compilation and the installers load large configuration and SQL files. The Composer scripts of the 2.5 generation already run the console with `-d memory_limit=2000M`. |
| `date.timezone` | set, for example `Europe/Berlin` or `UTC` | required by every line's guide ([list of time zones](https://www.php.net/manual/en/timezones.php)) |
| `max_execution_time` | 120 or more for the web, 300 or more (or unlimited) for the command line | the 7x guides; long imports and reindexing run on the command line |
| `opcache.enable` | `1` in production | performance |
| `realpath_cache_size` | 4096K or more | Symfony recommends a large realpath cache for big applications |
| `upload_max_filesize`, `post_max_size` | as large as the largest file editors upload | images and files are uploaded through the admin interfaces |

For a long command such as a full reindex, pass a larger limit on the command line rather than raising the web limit:

```bash
php -d memory_limit=-1 bin/console exponential:reindex --env=prod
```

## 2.3 PHP extensions

### Required

The `composer.json` of 1.1.0.x, 1.2.0.x and 1.3.0.x requires these extensions, so Composer refuses to install without
them:

| Extension | Used for |
|---|---|
| `gd` | image variations (thumbnails, crops); `imagick` can be used in addition |
| `curl` | HTTP clients: Solr, HTTP cache purging, remote media, external services |
| `json` | always present on PHP 8 |
| `xsl`, `xml` | RichText and the XML-based field types are rendered through XSLT |
| `intl` | locale handling, translations, collation |
| `mbstring` | multibyte strings |
| `ctype`, `iconv` | string handling in the kernel and Symfony |

Plus the PDO driver of your database:

| Database | Extensions |
|---|---|
| MySQL or MariaDB | `pdo_mysql` |
| PostgreSQL | `pdo_pgsql` |
| SQLite | `pdo_sqlite` and `sqlite3` |

The 2.5 generation declares no extensions in `composer.json` (and is installed with `--ignore-platform-reqs`, which
would skip them anyway). Its install guide (`v2.5.0.6`, and with the same list `1.0.0.9`) names these as mandatory: `ctype`, `date`, `dom`, `fileinfo`, `filter`,
`hash`, `iconv`, `intl`, `json`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql` (or `pdo_pgsql`, `pdo_sqlite`),
`phar`, `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter` and `zlib`; and `curl`, `gd` or `imagick`,
`opcache`, `apcu` and `zip` as strongly recommended. Add `xsl`: the legacy kernel's XML text and the RichText field
type need it.

### APCu on the 2.5 generation

The shipped `app/config/config.yml` of the 2.5 generation loads `cache_pool/cache.apcu.yml` and marks the APCu cache
pool "ACTIVE"; Doctrine's caches are configured with `type: apcu` as well. Without the `apcu` extension the
application does not start with that configuration. Install APCu ([installation](https://www.php.net/manual/en/apcu.installation.php))
or switch the cache pool in `app/config/config.yml` to `cache_pool/cache.tagaware.filesystem.yml` or Redis
([chapter 8](08-configuration.md)).

### Recommended

| Extension | Why |
|---|---|
| `opcache` | compiled PHP is cached; essential in production |
| `apcu` | a fast local cache; required by the 2.5 configuration as shipped |
| `imagick` | better image conversion than `gd` for large images |
| `redis` | when Redis is the application cache or session store (`CACHE_POOL=cache.redis`) |
| `zip` | Composer unpacks archives faster with it |
| `pcntl`, `posix` | long-running workers such as indexing and Messenger consumers (the 2.5 guide lists them as optional) |
| `sodium` | modern password hashing and JWT key handling (usually built in) |

### Finding what is missing

```bash
php -m | sort                                       # what the command line PHP has
php -m | grep -i -E 'sqlite|pdo'                    # for SQLite you need SQLite3 and pdo_sqlite
composer check-platform-reqs                        # after an install: what composer.lock needs that PHP lacks
```

`composer check-platform-reqs` reads the lock file, so it also reports extensions that dependencies (not only the
project) require. It is the quickest way to find what `--ignore-platform-reqs` hid on the 2.5 generation. Each line
names a requirement, the version found and a verdict; what needs action is every line that does not end in
`success`. Two such lines, in the shape Composer prints them (the package names depend on your lock file):

```text
ext-intl    n/a       <package> requires ext-intl (*)                      missing
php         8.0.30    se7enxweb/exponential requires php (^8.1 || ^8.2 ...)   failed
```

The command exits with a non-zero status when anything is missing, so it can guard a deploy script.

## 2.4 Databases

| Line | MySQL / MariaDB | PostgreSQL | SQLite | Default in the shipped configuration |
|---|---|---|---|---|
| 2.5 (`master`) | MySQL 5.7+ or MariaDB 10.2+ (the guide); utf8mb4 | PostgreSQL 12+ (the guide) | the guide mentions it for development; the database path is not wired into `app/config/config.yml` on this branch, so treat it as unverified | `pdo_mysql`, `mariadb-10.2.26`, `utf8mb4_unicode_520_ci` (`parameters.yml.dist`) |
| 1.0.0.x | as 2.5 | as 2.5 | yes: `pdo_sqlite` with the file `var/data_<env>.db` and the installer type `exponential-cjw` | as 2.5 |
| 1.1.0.x | MySQL 8.0+, MariaDB 10.3+ (10.6+ recommended) | 14+ | 3.35+; the installer fixes three composite primary keys | `.env`: a PostgreSQL `DATABASE_URL` placeholder; set your own in `.env.local` |
| 1.2.0.x | as 1.1.0.x | 14+ | 3.35+ | as 1.1.0.x |
| 1.3.0.x | as 1.1.0.x | 14+ | 3.35+; the reference installation runs on SQLite | as 1.1.0.x |

Whatever the engine, the character set is **utf8mb4** on MySQL and MariaDB with the collation
`utf8mb4_unicode_520_ci`, the value every line's configuration carries (`DATABASE_COLLATION`). PostgreSQL databases use
`UTF8`. The minimum versions are those of the 7x guides; the upstream requirements pages of each generation
([2.5](https://doc.ibexa.co/en/2.5/getting_started/requirements/),
[3.3](https://doc.ibexa.co/en/3.3/getting_started/requirements/),
[4.6](https://doc.ibexa.co/en/4.6/getting_started/requirements/),
[5.0](https://doc.ibexa.co/en/5.0/getting_started/requirements/)) list what upstream tested.

SQLite needs no server: the installer creates the database file. It suits development, demos and small sites; the
7x guides call it "dev / testing". [Chapter 7](07-databases.md) compares the engines, explains the SQLite settings
(`MESSENGER_TRANSPORT_DSN`, file permissions) and converting between engines.

## 2.5 Node.js and Yarn

The site's CSS and JavaScript, and on the newer lines the administration interface's assets, are compiled with
Webpack Encore. You need Node.js and Yarn on the machine that builds them, which can be a build server rather than
the production server ([chapter 9](09-frontend-and-themes.md)).

| Line | Node.js | Where it is declared | Yarn | Build tool |
|---|---|---|---|---|
| 2.5 (`master`) | 22 (`.nvmrc`, added 2026-10-05, released in `v2.5.0.7`); the scripts of `package.json` set `NODE_OPTIONS=--openssl-legacy-provider`, which lets the old webpack 4 build run on current Node.js (7x built the assets on Node.js 25) | `.nvmrc`: `v22`; no `engines`. The `v2.5.0.6` release has no `.nvmrc`, its guide says Node.js 18 or 20, and its scripts do **not** set the OpenSSL option | 1.x; npm works for the site build | `@symfony/webpack-encore ^0.27.0` (webpack 4) |
| 1.0.0.x | 20 (`nvm install 20` in the guide released with `1.0.0.9`) | `.nvmrc`: `v20` (added 2026-10-05, released in `1.0.0.11`); no `engines`. The scripts set the OpenSSL option, as on `master` | 1.x | as 2.5 |
| 1.1.0.x | 18 or 20 | `"engines": {"node": "^18 \|\| ^20"}`, `.nvmrc`: `v18` | 1.22 via corepack | Webpack Encore |
| 1.2.0.x | 18 | `"engines": {"node": "^18"}`, `.nvmrc`: `v18` | 1.22 via corepack | Webpack Encore |
| 1.3.0.x | 22 | `"engines": {"node": "^22"}`, `.nvmrc`: `v22` | 1.22 via corepack | Webpack Encore with `@ibexa/frontend-config` |

Notes:

- **On the 2.5 generation, Composer itself calls Yarn.** The `symfony-scripts` that run after `composer install` and
  `composer update` contain `yarn install` and `EzSystems\EzPlatformEncoreBundle\Composer\ScriptHandler::compileAssets`.
  Install Node.js and Yarn before Composer on these branches, or the scripts stop with an error.
- **Webpack 4 and OpenSSL 3.** Node.js 17 and later ship OpenSSL 3, whose default provider no longer offers the MD4
  hash webpack 4 uses. A webpack 4 build without `NODE_OPTIONS=--openssl-legacy-provider` stops with
  `Error: error:0308010C:digital envelope routines::unsupported` (`code: 'ERR_OSSL_EVP_UNSUPPORTED'`). The
  `package.json` of `master` and `1.0.0.x` sets the option in every script; the `package.json` released in `v2.5.0.6`
  (and `1.0.0.8`, `1.0.0.10`) does not, so with those tags either build on Node.js 16 or set it yourself:

  ```bash
  NODE_OPTIONS=--openssl-legacy-provider yarn build:prod
  ```

- **On 1.3.0.x, Node.js 22 is not optional.** The 7x guide: "The `@ibexa/frontend-config` package and its webpack
  configurations are not compatible with Node.js 20."
- **Use nvm.** The `.nvmrc` file lets `nvm use` (without a version) pick the right Node.js in the project root, and
  `corepack enable` activates the Yarn 1.22 that Node.js ships. The `make assets`, `make assets-prod` and
  `make ibexa-assets` targets run `nvm use || nvm install $(cat .nvmrc)` themselves; before 2026-10-05 the Makefile
  wrote that as `$(cat .nvmrc)` inside make, which expanded to nothing, so on an older checkout run `nvm install`
  yourself first.

```bash
# install nvm once, see https://github.com/nvm-sh/nvm
nvm install          # reads .nvmrc; on a release without one (v2.5.0.6 and older, 1.0.0.10 and older): nvm install 20
nvm use
corepack enable      # makes the yarn command available
node -v && yarn -v
```

Expected output on 1.3.0.x (the patch versions will differ):

```text
Found '/var/www/nexus/.nvmrc' with version <v22>
Now using node v22.x.y (npm v10.x.y)
v22.x.y
1.22.x
```

## 2.6 Composer

Composer 2 is required on every line ([installation](https://getcomposer.org/download/)). Keep it current
(`composer self-update`). Composer needs:

- write access to the project root and to its cache directory (`~/.cache/composer` by default);
- network access to Packagist and GitHub; GitHub rate limits are lifted by a token
  (`composer config --global github-oauth.github.com <token>`);
- enough memory: dependency resolution for these projects can need more than 1.5 GB. If it stops with an out-of-memory
  error, run it with `COMPOSER_MEMORY_LIMIT=-1`.

The 7x guides run Composer as root with `COMPOSER_ALLOW_SUPERUSER=1`. Prefer running it as the user that owns the
project files (the PHP-FPM user or a deploy user); [chapter 14](14-security-hardening.md) explains why.

## 2.7 Web server

| Line | Supported | Development | Document root and front controller |
|---|---|---|---|
| 2.5 generation | Apache 2.4 with `mod_rewrite` (prefork with mod_php or with PHP-FPM), nginx with PHP-FPM | PHP's built-in server: `php bin/console server:run -d web` (from the upstream notes) | `web/`, `web/app.php` |
| 1.1.0.x, 1.2.0.x, 1.3.0.x | Apache 2.4 with `mod_rewrite`, `mod_deflate`, `mod_headers`, `mod_expires`, event or worker MPM with PHP-FPM; nginx 1.18+ with PHP-FPM | Symfony CLI: `symfony server:start` | `public/`, `public/index.php` |

Example virtual hosts are in [doc/apache2/](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/apache2/Readme.md) and [doc/nginx/](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/nginx/Readme.md).
[Chapter 6](06-serving-the-site.md) covers serving the site in full, including Exponential Velocity, the application
server of the Exponential family, which ships a `symfony` preset.

## 2.8 Optional services

| Service | Version (7x guides) | Used for |
|---|---|---|
| Solr | 8.x, 8.11 recommended (1.1.0.x to 1.3.0.x); the 2.5 generation's upstream notes say 6.5+ | full-text search instead of the legacy (database) search engine: `SEARCH_ENGINE=solr` |
| Varnish | 6.0 minimum, 7.1+ recommended | HTTP cache in front of the site: `HTTPCACHE_PURGE_TYPE=varnish` |
| Redis | 6.0 minimum, 7.x recommended | application cache (`CACHE_POOL=cache.redis`), sessions |
| ImageMagick | any current release | image variations; the path is `IMAGEMAGICK_PATH=/usr/bin` in `.env` (1.1.0.x to 1.3.0.x) or `imagemagick_path: /usr/bin/convert` in `parameters.yml` (2.5 generation) |
| A mail server | any | contact forms (Netgen Information Collection) and user mails: `MAILER_DSN` |
| Sentry | any | error reporting, `SENTRY_DSN` (empty disables it) |

None of them is needed to install and run the demo site.

## 2.9 Operating system notes

Nexus runs wherever PHP, a database and Node.js run. Notes for the systems 7x uses:

- **Red Hat Enterprise Linux 9, AlmaLinux 9, Rocky Linux 9.** Current PHP comes from the Remi repository or from a
  control panel. Typical package names: `php-cli php-fpm php-intl php-xml php-gd php-mbstring php-pdo php-mysqlnd
  php-pgsql php-opcache php-pecl-apcu php-pecl-imagick` (`php-xml` brings `xsl`). SELinux has to allow PHP-FPM to
  write `var/` and the document root's `var/` (`httpd_sys_rw_content_t`).
- **Debian and Ubuntu.** Packages from the distribution or from the `ondrej/php` repository, named by version, for
  example `php8.4-intl php8.4-xml php8.4-gd php8.4-mbstring php8.4-mysql php8.4-pgsql php8.4-sqlite3 php8.4-apcu`.
- **Plesk.** Each domain runs in a PHP-FPM pool of one of Plesk's PHP builds under the domain's system user. Run the
  console with the same build (`/opt/plesk/php/<version>/bin/php`) and as that user, or give the files back to that user
  afterwards; the 7x guides show this for their own server, where commands run as root and the pool runs as the
  domain user, which is why they `chown` the SQLite file after every install.
- **macOS and WSL.** Fine for development with the Symfony CLI and SQLite.

## 2.10 Memory, CPU and disk

| Item | Size |
|---|---|
| `netgen/media-site-data` (the Netgen demo images and data) | about 180 MB (the `suggest` entry of the 2.5 `composer.json`) |
| Demo SQL of `exponential-media` | about 2.4 MB per database engine on 1.1.0.x and 1.2.0.x (`data/<engine>/media_data.sql`), about 4.3 MB on 1.3.0.x |
| SQL dumps of the CJW demo on 1.0.0.x | about 7 MB (MySQL), 14 MB (SQLite data) |
| `vendor/` and `node_modules/` | several hundred MB each; build `node_modules/` on a build machine if disk is tight |
| Memory | Composer resolution and container compilation are the peaks (see [2.6](#26-composer) and [2.2](#phpini-settings)); a running site needs what PHP-FPM's pool is configured for |

For production, size the database and `public/var/` (`web/var/` on the 2.5 generation; the legacy kernel's storage
on the lines that have one) by the content you expect: uploaded images and their variations dominate.

## References

In this repository:

- The project README and the 7x guide of each line: `README.md`, `doc/sevenx/INSTALL.md` (1.1.0.x to 1.3.0.x), and
  the older guides `doc/INSTALL.md` of the releases `1.0.0.9`, `v2.5.0.6`, `v1.1.0.7` and `v1.2.0.0`
  (`git show 1.0.0.9:doc/INSTALL.md`). Since 5 October 2026, [doc/INSTALL.md](../INSTALL.md) is the short guide into
  this book on every branch.
- `.nvmrc`, `package.json` and `Makefile` of the branch you install; `composer.json` of `se7enxweb/exponential` for the
  legacy kernel's PHP constraint ([Packagist](https://packagist.org/packages/se7enxweb/exponential)).
- [doc/netgen/INSTALL.md](../netgen/INSTALL.md) and [doc/netgen/FRONTEND.md](../netgen/FRONTEND.md) (upstream notes).
- [doc/apache2/](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/apache2/Readme.md), [doc/nginx/](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/nginx/Readme.md), [doc/varnish/](https://github.com/se7enxweb/exponential-platform-nexus/tree/master/doc/varnish).
- Chapters [6. Serving the site](06-serving-the-site.md), [7. Databases](07-databases.md),
  [9. Front end and themes](09-frontend-and-themes.md).

External:

- PHP: [supported versions](https://www.php.net/supported-versions.php), [APCu installation](https://www.php.net/manual/en/apcu.installation.php),
  [time zones](https://www.php.net/manual/en/timezones.php).
- Composer: [download](https://getcomposer.org/download/), [command line interface](https://getcomposer.org/doc/03-cli.md).
- Symfony: [setting up](https://symfony.com/doc/current/setup.html), [file permissions](https://symfony.com/doc/current/setup/file_permissions.html),
  [Symfony CLI server](https://symfony.com/doc/current/setup/symfony_cli.html), [Encore installation](https://symfony.com/doc/current/frontend/encore/installation.html).
- Node.js: [releases](https://nodejs.org/en/about/previous-releases), [the OpenSSL 3 change in Node.js 17](https://nodejs.org/en/blog/release/v17.0.0), [nvm](https://github.com/nvm-sh/nvm),
  [corepack](https://github.com/nodejs/corepack), [Yarn 1](https://classic.yarnpkg.com/en/docs/install).
- Upstream requirements: [2.5](https://doc.ibexa.co/en/2.5/getting_started/requirements/),
  [3.3](https://doc.ibexa.co/en/3.3/getting_started/requirements/), [4.6](https://doc.ibexa.co/en/4.6/getting_started/requirements/),
  [5.0](https://doc.ibexa.co/en/5.0/getting_started/requirements/).
- The [Exponential 6 book, chapter 2](https://github.com/se7enxweb/exponential/blob/main/doc/install/02-requirements.md)
  for the legacy kernel's own requirements.

[Contents](README.md) · Previous: [1. Introduction](01-introduction.md) · Next: [3. Getting the code](03-getting-the-code.md)
