# 4. Installing

This chapter takes a project that Composer has put on disk ([chapter 3](03-getting-the-code.md)) to a working site
you can log in to. It starts with what every line has in common (a database, its connection settings, the install
command, the front-end build, permissions, the first login), then gives the complete procedure for each line in its
own section, with the commands, the output to expect, and what can go wrong at each step. It ends with image
variations, re-running an install, and a checklist. Read the overview, then only the section of your line.

[Contents](README.md) · Previous: [3. Getting the code](03-getting-the-code.md) · Next: [5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md)

## 4.1 Overview: the same steps on every line

Every line installs in the same order. The order matters: the install command needs the database settings, the
front-end build needs the bundles that Composer installed, and the cache must be cleared after the settings change.

| Step | Why | 2.5 (`master`) | 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|---|---|
| 1. Create the database | the installer fills an existing database (except SQLite) | MySQL/MariaDB | MySQL/MariaDB, or SQLite | any of four | any of four | any of four |
| 2. Database settings | where the application connects | `app/config/parameters.yml` | `app/config/parameters.yml` | `.env.local` | `.env.local` | `.env.local` |
| 3. Install schema and demo content | creates the tables, loads content, layouts, tags, users | `ezplatform:install cjw-exponential-media` | `ezplatform:install netgen-media` or `exponential-cjw`, or the SQL dumps | `exponential:install exponential-media` | `exponential:install exponential-media` | `exponential:install exponential-media` |
| 4. Link files kept outside `vendor/` | the legacy directory is replaced on updates | manual symlinks | manual symlinks | automatic (Composer scripts) | automatic | not needed |
| 5. Front-end assets | CSS and JavaScript of the site | `yarn build:prod` (also run by Composer) | as 2.5 | `yarn build:prod` | `yarn build:prod` | `yarn build:prod` |
| 6. Admin assets | the administration interface's JavaScript | `composer ezplatform-assets` | as 2.5 | `yarn ez` | `composer ibexa-assets` | `composer ibexa-assets` |
| | (`make ibexa-assets` runs the right one on every line) | | | | | |
| 7. JWT keys | the REST API signs tokens with them | not used | not used | `lexik:jwt:generate-keypair` | same | same |
| 8. GraphQL schema | the admin interface and the `/graphql` endpoint | `ezplatform:graphql:generate-schema` | same | `ibexa:graphql:generate-schema` | same | same |
| 9. Permissions | PHP-FPM writes caches, logs, uploads, the SQLite file | `var/`, `web/var`, `ezpublish_legacy/var` | same | `var/`, `public/var`, `ezpublish_legacy/var` | same | `var/`, `public/var` |
| 10. Clear the cache | the container was compiled before the settings existed | `cache:clear` | same | same | same | same |
| 11. First login | prove it works, change the default password | `/admin`, `/legacy_admin`, `/ngadminui` via host names | same | `/adminui/` | `/adminui/` | `/adminui/` |

The administrator account of every demo data set is **`admin`** with the password **`publish`**. It is printed in
every README of the repository, so treat it as public: change it before the site is reachable from anywhere but your
own machine ([4.9](#49-the-first-login)).

## 4.2 Creating the database

### MySQL and MariaDB

Create an empty database with the character set and collation the configuration expects, and a user that owns it:

```sql
CREATE DATABASE nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'nexus'@'localhost' IDENTIFIED BY '<password>';
GRANT ALL PRIVILEGES ON nexus.* TO 'nexus'@'localhost';
```

Why each part:

- **`utf8mb4`** stores every Unicode character, including emoji. The older `utf8` (three bytes) cuts them off.
- **`utf8mb4_unicode_520_ci`** is the collation every line's configuration declares (`DATABASE_COLLATION` in `.env`,
  `env(DATABASE_COLLATION)` in `parameters.yml.dist`). A database with another collation still works, but tables
  created later by migrations then differ from the ones the installer created, and joins between them can fail with
  "Illegal mix of collations".
- **A separate user** limits what a leaked password can do. Do not install as `root`.

What can go wrong:

- The 1.0.0.x guide writes `COLLATE utf8mb4_unicode_general_ci`. That collation does not exist; MySQL answers
  `ERROR 1273 (HY000): Unknown collation`. Use `utf8mb4_unicode_520_ci`.
- The 7x guides of 1.1.0.x to 1.3.0.x write `GRANT ... IDENTIFIED BY ...` in one statement. MySQL 8.0 removed that
  form (`ERROR 1064`); create the user first, as above. MariaDB still accepts both.

### PostgreSQL

```bash
sudo -u postgres psql -c "CREATE USER nexus WITH PASSWORD '<password>';"
sudo -u postgres psql -c "CREATE DATABASE nexus OWNER nexus ENCODING 'UTF8';"
```

Making the user the **owner** matters on PostgreSQL 15 and later, where ordinary users may no longer create tables in
the `public` schema of a database they do not own; a `GRANT ALL PRIVILEGES ON DATABASE` alone (as in the 7x guides)
then ends in `permission denied for schema public` as soon as the installer creates its first table.

### SQLite

Nothing to create. The install commands of 1.1.0.x to 1.3.0.x detect SQLite, skip the "create database" step and let
the driver create the file on the first connection. You only need the `pdo_sqlite` and `sqlite3` extensions and a
writable `var/` directory.

## 4.3 The 2.5 line (`master`, `v2.5.0.x`)

This line installs the CJW demo ("JAC Example", German and English) on MySQL or MariaDB, with the legacy kernel
beside the Symfony 3.4 stack. The steps follow the line's install guide as released in `v2.5.0.6`
(`git show v2.5.0.6:doc/INSTALL.md`; on `master`, `doc/INSTALL.md` is now [the short guide](../INSTALL.md)),
corrected where noted.

**Release or branch.** `v2.5.0.6` is the newest release; `v2.5.0.7` is upcoming. The `master` branch has four fixes
that the release lacks and that matter for a public site: `web/app.php` no longer runs a request in the `dev`
environment because its `Host` header contains `dev.`, `app/AppCache.php` no longer makes private pages public, the
build scripts set the OpenSSL option current Node.js needs, and `.gitignore` keeps `.env.php` and `.env.local` out of
git. Install from the branch (`git clone -b master ...`, [chapter 3](03-getting-the-code.md#33-git-clone-and-composer-install))
or carry those changes into your copy of `v2.5.0.6`.

### Step 1: database settings in `parameters.yml`

`composer install` (or `create-project`) created `app/config/parameters.yml` from `app/config/parameters.yml.dist` and
asked for the values. Check or edit it:

```yaml
parameters:
    env(SYMFONY_SECRET): <a long random string>
    env(DATABASE_DRIVER): pdo_mysql
    env(DATABASE_HOST): 127.0.0.1
    env(DATABASE_PORT): 3306
    env(DATABASE_NAME): nexus
    env(DATABASE_USER): nexus
    env(DATABASE_PASSWORD): '<password>'
    env(DATABASE_CHARSET): utf8mb4
    env(DATABASE_COLLATION): utf8mb4_unicode_520_ci
    env(DATABASE_VERSION): mariadb-10.2.26     # or the version of your MySQL, for example 8.0
    imagemagick_path: /usr/bin/convert
    ngsite.default.site_domain: localhost
    ngsite.default.locations.site_info.id: 65
    ngsite.default.locations.tree_root.id: 168     # the root of the CJW content ("JAC Example")
```

Generate the secret with `openssl rand -hex 32`. The shipped placeholder
(`ThisEzPlatformTokenIsNotSoSecret_PleaseChangeIt`) is public, and the secret signs session-related and CSRF tokens.
`DATABASE_VERSION` tells Doctrine which SQL dialect to use without connecting first; set it to your server's version.

**The tree root must be 168.** The CJW content puts its site, "JAC Example", at location 168 (`/1/2/168/`), and the
public siteaccesses serve `index_page: /startseite`, a page below it. `ngsite.default.locations.tree_root.id` sets
the front-end root of `de` and `en` and the legacy kernel's `RootNode`. What the line ships does not get this right:

| Version | `ngsite.default.locations.tree_root.id` as shipped | Result |
|---|---|---|
| `v2.5.0.6` | not defined in `parameters.yml.dist` or `default_parameters.yml` | the container does not build: `You have requested a non-existent parameter "ngsite.default.locations.tree_root.id"` |
| `master` | `2` in `parameters.yml.dist` | the site starts, but at the content root above "JAC Example"; seen from there the start page's URL alias is `jac_example/startseite`, so `index_page: /startseite` points at nothing |

Write `168` into `app/config/parameters.yml` as above. (The `1.0.0.x` branch now ships 168,
[4.4](#44-the-100x-branch).)

### Step 2: install the schema and the content

```bash
php bin/console ezplatform:install cjw-exponential-media
```

The type is registered in `src/AppBundle/Resources/config/services.yml` (tag `ezplatform.installer`, type
`cjw-exponential-media`) and reads the package `se7enxweb/cjw-exponential-media-site-data`, which Composer installed:
`cjw-exponential-media/data.sql` (about 7 MB of content) and `schema/schema.sql`. The same command is the Composer
script `composer ezplatform-install`. The data files are MySQL dumps, so this type needs MySQL or MariaDB.

What can go wrong:

- **Which types exist.** Three sources register types on this line: the project (`cjw-exponential-media`), the kernel
  fork `se7enxweb/ezpublish-kernel` (`clean` and `exponential-oss`, both an empty repository; checked in `v7.5.41`, the
  version `~7.5.40` resolves to today) and `netgen/site-installer-bundle` (`netgen-media`, `netgen-media-clean`,
  `netgen-media-remote-clean`). An unknown type ends the command with the list of known ones.
- **`netgen-media` is registered but has no data.** Since `v2.5.0.6` the data package `netgen/media-site-data`
  (about 180 MB) is only suggested, so the type exists while its SQL and images do not, and the installer fails when
  it reads them. Install the package first if you want the Netgen demo instead:
  `composer require --ignore-platform-reqs netgen/media-site-data:~1.8.1`. The Netgen demo's root is location 2, so
  set `ngsite.default.locations.tree_root.id: 2` for it.
- **SQLite.** The guide describes `pdo_sqlite` for development, but on `master` the SQLite file path is not wired into
  `app/config/config.yml` (it is on `1.0.0.x`, [4.4](#44-the-100x-branch)), and the CJW data files are MySQL dumps.
- **A second run.** The command drops every table it knows and asks first; see [4.11](#411-running-the-install-again).

A successful run ends after the schema, the data and the search index; look for the CJW data file in its output and
check the result with one query:

```bash
mysql -u nexus -p nexus -e "SELECT path_identification_string FROM ezcontentobject_tree WHERE node_id = 168;"
# jac_example
```

### Step 3: the front-end assets

The site's CSS and JavaScript are built with Webpack Encore from `src/AppBundle/Resources/`. Composer already ran
`yarn install` and built them; after a change, or if that step failed:

```bash
nvm use               # master: .nvmrc says v22; v2.5.0.6 has no .nvmrc, use: nvm use 20
yarn install          # or: npm install
yarn build:prod       # or: npm run build:prod
```

On `master` the `package.json` scripts set `NODE_OPTIONS=--openssl-legacy-provider` so the webpack 4 build also runs on
Node.js releases with OpenSSL 3. The `package.json` of `v2.5.0.6` does not: there the build stops with
`ERR_OSSL_EVP_UNSUPPORTED` on Node.js 17 and later unless you run
`NODE_OPTIONS=--openssl-legacy-provider yarn build:prod` ([chapter 2](02-requirements.md#25-nodejs-and-yarn)). The
same applies to the `yarn install` and asset build that `composer install` runs. If the build is skipped, the site
fails with HTTP 500 and
`EntrypointNotFoundException: Could not find the entry "photoswipe-init"`: the templates ask for an entry point that
was never built. The administration's assets are built by `composer ezplatform-assets` (it dumps the JavaScript
translations and runs `yarn ezplatform`).

### Step 4: the symlinks into the legacy kernel

The legacy kernel lives in `ezpublish_legacy/`, which Composer replaces whenever `se7enxweb/exponential` is updated.
The project therefore keeps its legacy extension and its uploaded files in `src/AppBundle/ezpublish_legacy/` and links
them in. Run in the project root:

```bash
ln -s ../../src/AppBundle/ezpublish_legacy/extension/app ezpublish_legacy/extension/app
mv ezpublish_legacy/var/site/storage ezpublish_legacy/var/site/storage-empty
ln -s ../../../src/AppBundle/ezpublish_legacy/var/site/storage ezpublish_legacy/var/site/storage
ln -s ../../src/AppBundle/Resources/public web/bundles/app
```

These are the links of the line's guide (`v2.5.0.6`), written from the project root (the guide `cd`s into each directory). Each
link target is relative to the directory that holds the link. The guide writes the first target as `../../../src/...`; seen from
`ezpublish_legacy/extension/`, three levels up is the directory *above* the project root, so that link dangles unless
your layout differs. A working 2.5 installation checked for this book uses `../../src/...`, as above. Check each link
with `ls ezpublish_legacy/extension/app/`, which must list `extension.xml`. If a link already exists (`ln: failed to create
symbolic link ...: File exists`), a Composer script (`ngsite:symlink:legacy`, `ezpublish:legacybundles:install_extensions`)
created it already; leave it. The `1.0.0.x` guide also links the `ngadminui` legacy extension from
`vendor/se7enxweb/admin-ui-bundle/bundle/ezpublish_legacy/ngadminui`; `ezpublish:legacybundles:install_extensions`
normally installs it during `composer install`, so check `ls -l ezpublish_legacy/extension/` before adding it by hand.

**Repeat these links after every `composer update`** that updates `se7enxweb/exponential`. Without them, images of
the demo content are missing and the `app` extension's settings are gone.

### Step 5: permissions and cache

```bash
sudo chown -R <user>:<web-group> .
sudo chmod -R u+rwX,g+rwX var web/var ezpublish_legacy/var
php bin/console cache:clear --env=prod
```

Run the console as the same user as PHP-FPM, or the cache files it writes cannot be replaced by the web server later
(`Unable to create the store directory`, `Failed to remove file`). The guide's `chmod -R 755 .` and "always use sudo"
make every file writable only by root; avoid that ([chapter 14](14-security-hardening.md)).

### Step 6: siteaccesses and the first request

`app/config/ezplatform_siteaccess.yml` defines `de` (default), `en`, `admin`, `ngadminui` and `legacy_admin`. `de` and
`en` (and `ngadminui` on `master`) are matched by the first path element; all of them are also matched by host name,
and the shipped host names are those of the CJW demo servers. Replace them with yours, for example:

```yaml
        match:
            Map\URI:
                en: en
                de: de
                ngadminui: ngadminui
            Map\Host:
                www.example.com: de
                admin.example.com: admin
                legacy.example.com: legacy_admin
```

Two host names (one public, one for editors) is what the guide recommends. The front-end siteaccesses serve the
`index_page: /startseite` of the CJW content; their tree root is `ngsite.default.locations.tree_root.id`, 168 for the
CJW content (step 1).

Two more files carry the demo servers' host names:

- `app/config/http_cache.yml` lists the hosts, siteaccesses and paths whose responses `app/AppCache.php` must never
  make public (`uncached_hostnames`, `uncached_siteaccesses`, `uncached_url_patterns`). Replace the demo host names
  with your editors' host names. On `master`, `AppCache` also always excludes the admin siteaccesses, `/nglayouts`,
  `/graphql`, `/api/` and the login pages, and reads extra host names from the comma-separated environment variable
  `HTTP_CACHE_UNCACHED_HOSTNAMES` ([chapter 6](06-serving-the-site.md#67-the-http-cache-symfony-proxy-varnish-and-foshttpcache)).
- `web/.htaccess` links to `src/AppBundle/Resources/symlink/root_dev/.htaccess`, which sets `SYMFONY_ENV=prod`. On
  `1.0.0.x` that file also requires HTTP Basic authentication against a password file of the demo servers; on `master`
  those lines are commented out. See [chapter 6](06-serving-the-site.md#634-serve-webappphp-the-100x-line).

A quick test from the server itself, before DNS points at it (the host name must be one of your `Map\Host` entries):

```bash
curl -sI -H 'Host: www.example.com' http://127.0.0.1/ | head -1     # the German start page
curl -sI -H 'Host: www.example.com' http://127.0.0.1/en | head -1   # the English start page
```

Expect `200` (or a `301`/`302` to the start page). A `404` usually means a wrong tree root; a `500` is explained in
`var/logs/prod.log`. This check was not run against a 2.5 installation for the book; it only uses what the
configuration above defines.

Then continue with [4.9](#49-the-first-login).

## 4.4 The 1.0.0.x branch

The `1.0.0.x` branch shares the stack of the 2.5 line and differs in its demo data. Its newest release is `1.0.0.9`
(`1.0.0.10` is `master` code, [chapter 3](03-getting-the-code.md#31-branches-tags-and-packagist-versions)); the
branch has the same fixes to `web/app.php`, `AppCache` and `.gitignore` as `master` (section
[4.3](#43-the-25-line-master-v250x)), an `.nvmrc` (`v20`) and the tree root fix below, none of them released yet. There are three ways to fill the database:

| Way | Database | Content |
|---|---|---|
| `ezplatform:install netgen-media` | MySQL, MariaDB | the Netgen demo of `netgen/media-site-data ~1.8.1`; `netgen-media-clean` installs no content |
| `ezplatform:install exponential-cjw` (from `1.0.0.6`) | SQLite (on MySQL it falls back to the clean platform data) | the CJW content from `src/AppBundle/Resources/database/sql/sqlite/` |
| import the SQL dumps | MySQL, MariaDB | the CJW content: `src/AppBundle/Resources/database/sql/starter_project_database_sql_dump.sql` (all), or `schema/schema.sql` plus `data/content.sql` |

**The tree root.** The CJW content's site root is location 168. The branch now ships 168 in both
`parameters.yml.dist` and `default_parameters.yml` (since 2026-10-05). Up to the release `1.0.0.9`,
`default_parameters.yml` said 168 but `parameters.yml.dist` said 2, and because `parameters.yml` is imported after
`default_parameters.yml`, the 2 won. Whatever you installed, check `app/config/parameters.yml`: 168 for the CJW
content (SQL dumps, `exponential-cjw`), 2 for `netgen-media` (whose location 168 is an article) or an empty
repository.

### SQLite with `exponential-cjw`

The branch reads `database_path` (default `var/data_<env>.db`, set in `default_parameters.yml`) for the `pdo_sqlite`
driver. In `app/config/parameters.yml`:

```yaml
    env(DATABASE_DRIVER): pdo_sqlite
    ngsite.default.locations.tree_root.id: 168     # the root of the CJW content
    # optional, another file than var/data_<env>.db; no environment variable is read for it:
    # database_path: /var/www/nexus/var/nexus.db
```

Then:

```bash
php bin/console ezplatform:install exponential-cjw
```

`AppBundle\Installer\ExponentialCjwInstaller` creates every table from `sqlite/schema.sql` (which keeps the composite
primary keys of the versioned tables) and loads `sqlite/data.sql`. Expect lines like
`Creating all tables (eZ Platform + CJW) from .../sqlite/schema.sql` and
`Executing <n> queries from .../sqlite/data.sql on database ...`.

### MySQL with the SQL dumps

```bash
mysql -u nexus -p nexus < src/AppBundle/Resources/database/sql/starter_project_database_sql_dump.sql
```

Keep `ngsite.default.locations.tree_root.id: 168` for the CJW content (the comment in `default_parameters.yml`:
"168 is the site root of the shipped database; use 2 (the Content root) with netgen-media data or an empty
repository"). Then do steps 3 to 6 of
[4.3](#43-the-25-line-master-v250x): the guide of this branch has the same symlinks, permissions and cache clear.

## 4.5 The 1.1.0.x line

The 3.3 generation with Symfony 5.4 and the legacy kernel. The installer of this line is the project's own
`exponential:install` (in `src/Command/`); it works on MySQL, MariaDB, PostgreSQL and SQLite.

### Step 1: `.env.local`

Symfony reads `.env` and then `.env.local`, which wins. Never edit `.env` (it is committed and is replaced on updates);
put your values in `.env.local`:

```bash
touch .env.local
```

Start with an empty file rather than copying `.env.local.dist`: that file is Netgen's template for their local
development setup and sets `SERVER_ENVIRONMENT=local`, which needs a `config/app/server/local.yaml` the project does
not ship, and a MySQL URL with placeholder credentials.

```dotenv
APP_ENV=dev
APP_SECRET=<output of: openssl rand -hex 32>
SERVER_ENVIRONMENT=dev

# one of:
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
# DATABASE_URL="mysql://nexus:<password>@127.0.0.1:3306/nexus?serverVersion=8.0&charset=utf8mb4"
# DATABASE_URL="mysql://nexus:<password>@127.0.0.1:3306/nexus?serverVersion=mariadb-10.6.0&charset=utf8mb4"
# DATABASE_URL="postgresql://nexus:<password>@127.0.0.1:5432/nexus?serverVersion=16&charset=utf8"

# with SQLite, the 7x guide processes queued messages synchronously:
MESSENGER_TRANSPORT_DSN=sync://
```

Why:

- **`DATABASE_URL` is the only database setting the application reads** (`config/packages/doctrine.yaml`:
  `url: '%env(resolve:DATABASE_URL)%'`). The committed `.env` contains a PostgreSQL placeholder
  (`postgresql://app:!ChangeMe!@127.0.0.1:5432/app`), so without your own `DATABASE_URL` the install tries to reach a
  PostgreSQL server that does not exist. The 7x guide also lists `DATABASE_DRIVER`, `DATABASE_HOST` and similar
  variables; the project's configuration does not read them.
- **Encode special characters** in the password (`@` as `%40`, `/` as `%2F`), or the URL is parsed wrongly.
- **`%kernel.environment%` in the SQLite path** gives each environment its own file: `var/data_dev.db` for `dev`,
  `var/data_prod.db` for `prod`. The web server and the console must use the same environment, or they see two
  different databases ([4.10](#410-what-can-go-wrong-across-lines)).
- **`SERVER_ENVIRONMENT`** selects `config/app/server/<value>.yaml`, which holds the location IDs of the demo content.
  This line ships `dev` and `prod`. It is independent of `APP_ENV`. `prod.yaml` reads `APP_DOMAIN`, `MAIL_FROM`,
  `MAIL_TO` and `GTM_CODE`, and `config/app/app.yaml` reads `TEST_DOMAIN`; until 2026-10-05 no `.env` defined them and
  `SERVER_ENVIRONMENT=prod` failed with `Environment variable not found: "APP_DOMAIN"`, so with `v1.1.0.7` define
  them in `.env.local` yourself (the branch's `.env` now gives each a default). Both files of this line
  set `ngsite.bold_group.locations.tree_root.id: 2`, while the Bold Agency site of the demo data is location 386;
  set 386 in the file you use (`config/app/server/dev/app.yaml` or `prod.yaml`) if `bold_eng` should start at the
  Bold Agency home page.
- **`MESSENGER_TRANSPORT_DSN`.** The committed default is `doctrine://default?auto_setup=0`. The 7x guide sets `sync://`
  for SQLite; the 1.3.0.x reference installation runs SQLite with the default and works. `sync://` is the simpler choice
  for a single server.

### Step 2: install

```bash
php bin/console exponential:install exponential-media --no-interaction
```

What the command does, in order (from `src/Command/ExponentialInstallCommand.php` and
`src/Installer/ExponentialMediaInstaller.php`):

1. checks that `public/` or `public/var` is writable, else stops with `[public/ | public/var] is not writable`;
2. creates the database if it does not exist; on SQLite prints
   `SQLite detected — skipping doctrine:database:create (... will be created automatically).`;
3. asks `Running this command will delete data in all eZ Platform generated tables. Continue?` when tables exist
   (`--no-interaction` answers yes);
4. creates the platform schema, then runs `data/sqlite/media_schema.sql` (Layouts, Tags and other extra tables);
5. on SQLite, recreates `ezcontentobject_attribute`, `ezcontentclass` and `ezcontentclass_attribute` with their
   composite primary key `(id, version)`, which Doctrine's SQLite schema loses; without this every second draft fails
   with `UNIQUE constraint failed: ezcontentobject_attribute.id`;
6. loads `data/<engine>/media_data.sql` and copies the demo images to `public/var/site/storage`;
7. clears the persistence cache and runs `exponential:reindex` (skip it with `--skip-indexing`).

**What can go wrong on MySQL and PostgreSQL.** Step 4 asks for `data/<engine>/media_schema.sql`, and the branch ships
that file only for SQLite (`data/sqlite/media_schema.sql`). Read from the code, the installer then stops with
`DBMS-specific file for mysql database platform does not exist or is not readable`. This was not run for the book;
if you meet it, either install on SQLite, or use the Netgen installer of this line, which reads the MySQL dump of
`se7enxweb/media-site-data`:

```bash
php bin/console ibexa:install netgen-media          # or netgen-media-clean; MySQL and MariaDB only
```

(`netgen-media` is registered with the upstream install command `ibexa:install`, not with `exponential:install`,
which only knows the types tagged `exponential.installer`.)

### Step 3: SQLite file ownership

When the console ran as another user than PHP-FPM, give the database file to the web server's user, or every request
that writes (logins, edits) fails with `attempt to write a readonly database`:

```bash
chown <fpm-user>:<fpm-group> var var/data_<env>.db     # data_dev.db with APP_ENV=dev
chmod 660 var/data_<env>.db
```

SQLite also writes a journal file next to the database, which is why the directory `var/` must be writable too.

### Step 4: assets, keys, schema, cache

```bash
nvm use                                   # .nvmrc: v18
corepack enable
yarn install
yarn build:prod                           # the site's CSS and JavaScript
php bin/console assets:install --symlink --relative public
yarn ez                                   # the administration interface (webpack.config.ez.js)
php bin/console lexik:jwt:generate-keypair
php bin/console ibexa:graphql:generate-schema
php bin/console cache:clear
```

The line's `doc/INSTALL.md` uses `composer ez-assets`; there is no such Composer script on this branch. Use `yarn ez`.

The legacy kernel's settings for `ngadminui` and `legacy_admin` are linked from `src/install/` by
`bin/create_install_symlinks.php`, which `composer install` runs, so there are no manual symlinks on this line.

### Step 5: start and log in

For development, the Symfony CLI serves `public/`:

```bash
symfony server:start
```

| URL | What |
|---|---|
| `https://127.0.0.1:8000/` | Fit & Healthy (`fh_eng`, the default siteaccess) |
| `https://127.0.0.1:8000/bold_eng/`, `/bold_ger/` | Bold Agency in English and German |
| `https://127.0.0.1:8000/adminui/` | the administration interface |
| `https://127.0.0.1:8000/ngadminui/` | the Netgen admin UI |
| `https://127.0.0.1:8000/legacy_admin/` | the legacy kernel's administration |
| `https://127.0.0.1:8000/adminui/nglayouts/admin` | the Netgen Layouts admin |

## 4.6 The 1.2.0.x line

The 4.6 generation with Symfony 5.4. The procedure is that of 1.1.0.x with these differences:

| | 1.2.0.x |
|---|---|
| Node.js | 18 (`.nvmrc`: `v18`) |
| Install command | `exponential:install` from `src/RepositoryInstaller/`; it knows every type tagged `ibexa.installer`: `exponential-media`, `netgen-media` (from `netgen/site-installer-bundle 3.1`), and the platform's clean type. Its default type is `ibexa-oss`, so always name the type. |
| Admin assets | `composer ibexa-assets` (dumps JavaScript translations, runs `yarn ibexa`) |
| `SERVER_ENVIRONMENT` | `dev`, and on the branch since 2026-10-05 also `prod` (`config/app/server/prod.yaml`, which reads `SITE_DOMAIN`, `COLLECTED_INFO_SENDER`, `COLLECTED_INFO_RECIPIENT` and `GOOGLE_TAG_MANAGER_CODE`, with defaults); `v1.2.0.0` ships only `dev` ([4.10](#410-what-can-go-wrong-across-lines)) |
| Legacy kernel | installed through `se7enxweb/site-legacy-bundle` and `se7enxweb/ibexa-legacy-bridge` |

```bash
php bin/console exponential:install exponential-media --no-interaction
chown <fpm-user>:<fpm-group> var var/data_<env>.db    # SQLite only
nvm use && corepack enable
yarn install && yarn build:prod
php bin/console assets:install --symlink --relative public
composer ibexa-assets
php bin/console lexik:jwt:generate-keypair
php bin/console ibexa:graphql:generate-schema
php bin/console cache:clear
```

The line's 7x guide writes `yarn ibexa:build` for the admin assets. The `package.json` of 1.2.0.x has no script of that
name (it is `ibexa`), so `yarn ibexa:build` stops with `Command "ibexa:build" not found`. The same media-schema caveat
as on 1.1.0.x applies on MySQL and PostgreSQL (only `data/sqlite/media_schema.sql` ships); `netgen-media` is the MySQL
alternative and is available through `exponential:install` on this line.

The URLs are those of 1.1.0.x. The 7x guide of the line shows siteaccesses `site` and `admin`; the configuration in
`config/app/packages/ibexa_siteaccess.yaml` defines `fh_eng`, `bold_eng`, `bold_ger`, `adminui`, `ngadminui` and
`legacy_admin`, and that is what the site answers to.

## 4.7 The 1.3.0.x line

The 5 generation with Symfony 7.4, without the legacy kernel. This is the line the reference installation behind this
book runs (on SQLite, PHP 8.5).

### Step 1: `.env.local`

As on 1.1.0.x ([4.5](#step-1-envlocal)). The committed `.env` again carries the PostgreSQL placeholder `DATABASE_URL`;
the 7x guide's remark that SQLite "is the default already set in `.env`" is true of the Nexus starter repository, not of
this branch. Set:

```dotenv
APP_ENV=prod
APP_SECRET=<output of: openssl rand -hex 32>
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"
SERVER_ENVIRONMENT=dev
```

**Choose `APP_ENV` before you install, and use the same one everywhere.** The committed `.env` says `APP_ENV=dev`,
but since `1.3.0.5` the file `assets/symlink/root_dev/.htaccess` sets `APP_ENV=prod` and `APP_DEBUG 0`, and
`ngsite:symlink:project` (run by `composer install`) links it as `public/.htaccess`. Under Apache every web request
then runs in `prod`, while a console without `APP_ENV` in `.env.local` runs in `dev`. With the SQLite URL above the
two use different files, `var/data_prod.db` and `var/data_dev.db`, and the site shows an empty database although the
install "worked". Setting `APP_ENV=prod` in `.env.local`, as above, makes the console, the installer and the web
agree. For development set `APP_ENV=dev` there and give the web server the same value (section
[4.10](#410-what-can-go-wrong-across-lines)).

`SERVER_ENVIRONMENT` selects `config/app/server/<value>.yaml`. `dev.yaml` imports `dev/app.yaml` and
`dev/ibexa_siteaccess.yaml`; `dev/app.yaml` sets the location IDs the designs need (Fit & Healthy root 385, Bold
Agency root 386, site info 65 and 442) and the domains (`localhost`). Since 2026-10-05 the branch also ships
`prod.yaml` with the same location IDs, the domain and the information collection addresses taken from
`SITE_DOMAIN`, `COLLECTED_INFO_SENDER` and `COLLECTED_INFO_RECIPIENT`, and an empty Google Tag Manager code instead of
the demo's; `1.3.0.5` ships only `dev.yaml`. `deploy/files/.env.local.prod` sets `SERVER_ENVIRONMENT=prod`, which
therefore only works from the branch.

### Step 2: install

**First, update the core package.** The installer lives in `se7enxweb/exponential-platform-dxp-core`. The lock file
of `1.3.0.5` and of the branch pins its `v5.0.7`, whose installer looks for the Netgen Layouts schema only in
`vendor/netgen/layouts-core/`. This line installs the fork `se7enxweb/layouts-core` instead (it `replace`s the
upstream name and lives in `vendor/se7enxweb/layouts-core/`), so `v5.0.7` skips the `nglayouts_*` tables without a
word, and the demo data, which fills those tables, then fails with `no such table: nglayouts_...` (or the MySQL and
PostgreSQL equivalent). `v5.0.9`, released on 2026-10-05, looks in both places and says which file it used. Update
that one package before the first install:

```bash
composer update se7enxweb/exponential-platform-dxp-core
composer show se7enxweb/exponential-platform-dxp-core | grep -E '^versions'    # versions : * v5.0.9
```

(The behaviour of `v5.0.7` was read from its code, not reproduced for the book. If you cannot update, create the
Layouts tables with the Layouts migrations, [chapter 7](07-databases.md#75-migrations), before you run the install.)

Then install:

```bash
php bin/console exponential:install exponential-media
```

The command comes from `se7enxweb/exponential-platform-dxp-core` (`Ibexa\Bundle\RepositoryInstaller\Command\InstallPlatformCommand`,
named `exponential:install`), and so does the data: `vendor/se7enxweb/exponential-platform-dxp-core/data/<engine>/`
contains `media_schema.sql` and `media_data.sql` for MySQL, PostgreSQL and SQLite alike. Typical output with `v5.0.9`
on SQLite (shortened; `<n>` stands for a count):

```
SQLite detected — skipping doctrine:database:create (file will be created automatically).
Executing <n> queries on database ... (sqlite)
Importing Netgen Layouts schema from .../vendor/se7enxweb/layouts-core/tests/_fixtures/schema/schema.sqlite.sql
Executing <n> queries from .../vendor/se7enxweb/layouts-core/tests/_fixtures/schema/schema.sqlite.sql on database ...
Executing <n> queries from .../data/sqlite/media_schema.sql on database ...
Executing <n> queries from .../data/sqlite/media_data.sql on database ...
Copying storage directory to .../public/var/site/storage

 [WARNING] For security reasons, you're required to change the default admin password. ...

 Password (your input will be hidden):
Search engine re-indexing, executing command exponential:reindex
```

If the line `Importing Netgen Layouts schema` is missing, or you see `Netgen Layouts schema not imported: no
layouts-core package found`, stop: the Layouts tables were not created, and the demo will not load.

Check the result:

```bash
php bin/console dbal:run-sql "SELECT COUNT(*) FROM nglayouts_layout WHERE status = 1"     # 21
php bin/console dbal:run-sql "SELECT COUNT(*) FROM ibexa_content WHERE status = 1"        # 290
```

(The figures are those of the reference installation, [chapter 5](05-the-demo-site-and-layouts.md); `dbal:run-sql`
prints them as a small table; `doctrine:query:sql` is its deprecated older name.)

Differences from the older lines:

- **The admin password is changed during the install** when the command runs interactively: it asks until
  `ibexa:user:update-user` accepts a password that meets the password rules. With `--no-interaction` the question is
  skipped and the password stays `publish`; change it at the first login.
- **The installer creates the Netgen Layouts tables itself** from the schema file of the installed layouts-core
  package (with core `v5.0.9`: the fork `se7enxweb/layouts-core` first, then `netgen/layouts-core`).
- **The database error names the right file** with core `v5.0.9`: when the database cannot be created, the message
  points to `DATABASE_URL` in `.env.local`. Older cores tell you to check `app/config/parameters.yml`, a file this
  line does not have.
- **The images** come from `vendor/netgen/media-site-data/netgen-media/storage` and are copied to
  `public/var/site/storage`, unless that directory already has files (`... already exists and is not empty, skipping`).
- Available types: `exponential-media` (the demo), `exponential-oss` (the clean platform content; registered by the
  core package and again by the project's `src/Installer/ExponentialOssInstaller.php`), `ibexa-oss` (the default when no
  type is given) and `netgen-media` (MySQL data of `netgen/media-site-data`). `php bin/console exponential:install --help`
  lists them for your checkout.

### Step 3: assets, keys, schema, cache

```bash
chown <fpm-user>:<fpm-group> var var/data_prod.db     # SQLite only; data_<APP_ENV>.db
nvm use                                                # .nvmrc: v22
corepack enable
yarn install
yarn build:prod                                        # webpack.config.project.js: the site
php bin/console assets:install --symlink --relative public
composer ibexa-assets                                  # or: yarn ibexa:build
php bin/console lexik:jwt:generate-keypair
php bin/console ibexa:graphql:generate-schema
php bin/console cache:clear
```

`assets:install` must run before the admin build: it writes `var/encore/ibexa.config*.js`, the files that tell the
admin build where each bundle's sources are. Without them `yarn ibexa:build` fails with "Module not found".
`composer ibexa-assets` runs the whole sequence the project defines (`yarn ibexa-generate-tsconfig`, the translation
dump and two `ibexa:encore:compile` runs).

### Step 4: check the bundles Flex may have removed

```bash
grep -c -E 'NetgenLayoutsBundle|NetgenLayoutsAdminBundle' config/bundles.php    # expect 2
ls config/routes/netgen_layouts.yaml
```

If either is missing, a Composer run unconfigured the `netgen/layouts-core` recipe ([chapter 3](03-getting-the-code.md#forks-replace-upstream-packages));
restore with `git checkout -- config/bundles.php config/routes/netgen_layouts.yaml` and clear the cache. The symptom is
`Container extension "netgen_layouts" is not registered`.

### Step 5: start and log in

```bash
symfony server:start
```

| URL | What |
|---|---|
| `https://127.0.0.1:8000/` | Fit & Healthy (`fh_eng`) |
| `https://127.0.0.1:8000/bold_eng/`, `/bold_ger/` | Bold Agency |
| `https://127.0.0.1:8000/adminui/` | the administration interface |
| `https://127.0.0.1:8000/adminui/nglayouts/admin` | the Netgen Layouts admin, reached through the admin siteaccess |
| `https://127.0.0.1:8000/api/ezp/v2/` | REST API |
| `https://127.0.0.1:8000/graphql` | GraphQL |

The 7x guide lists `site` and `admin` siteaccesses and `/nglayouts/admin` at the root. The configuration defines
`fh_eng`, `bold_eng`, `bold_ger` and `adminui` (the parameter `ngsite.admin_siteaccess_name`), matched by the first
path element.

## 4.8 Image variations

The demo images are large. Every page view that needs a size that does not exist yet generates it on the spot, which
makes the first visits slow. Generate the common sizes in advance (all lines with the Media Site bundle):

```bash
php bin/console ngsite:content:generate-image-variations --variations=i30,i160,i320,i480,nglayouts_app_preview,ngcb_thumbnail
php bin/console ngsite:content:generate-image-variations --help     # limit by subtree, content type, field
```

It takes a few minutes. Run it as the PHP-FPM user, because it writes into `public/var` (`web/var`).

## 4.9 The first login

1. Open the administration URL of your line and log in as `admin` with the password `publish` (or the one you set
   during an interactive 1.3.0.x install).
2. Change the password at once: in the administration interface open the user menu and change the password, or on the
   command line on 1.3.0.x: `php bin/console ibexa:user:update-user admin --password='<new password>'`.
3. Open the public site, follow a few links, open an article: if pages render with images and menus, the content,
   the Layouts mappings and the image storage are in place.
4. Open the Layouts admin and check that the layouts are listed ([chapter 5](05-the-demo-site-and-layouts.md)).
5. Look at the logs: `var/log/<env>.log` (`var/logs/` on the 2.5 generation).

## 4.10 What can go wrong across lines

| Symptom | Cause | Fix |
|---|---|---|
| The site shows an empty database or "no such table" while the console works | the web server runs another `APP_ENV` than the console, and the SQLite path contains `%kernel.environment%`. `ngsite:symlink:project` (run by Composer) links `public/.htaccess` to `assets/symlink/root_<console environment>/.htaccess`, and on 1.3.0.5 `root_dev/.htaccess` sets `APP_ENV=prod` for every web request, so the web server reads `var/data_prod.db` while a `dev` console install wrote `var/data_dev.db` | install with the same environment as the web server (`php bin/console exponential:install exponential-media --env=prod`), or use a fixed file name in `DATABASE_URL` (`var/data.db`) |
| `Unable to find file ".../config/app/server/prod.yaml"` (or similar) | `SERVER_ENVIRONMENT` names a file that does not exist: the releases `v1.2.0.0` and `1.3.0.5` ship only `dev` (the branches have `prod` since 2026-10-05), and any other name needs its own file | keep `SERVER_ENVIRONMENT=dev`, take `prod.yaml` from the branch, or copy `config/app/server/dev.yaml` and `dev/` to your own name and adapt the IDs and domains |
| `Environment variable not found: "APP_DOMAIN"` (1.1.0.x) | `SERVER_ENVIRONMENT=prod` with the `.env` of `v1.1.0.7` | define `APP_DOMAIN`, `MAIL_FROM`, `MAIL_TO`, `GTM_CODE` and `TEST_DOMAIN` in `.env.local` |
| `no such table: nglayouts_...` during `exponential:install` (1.3.0.x) | the locked core `v5.0.7` did not create the Layouts tables | `composer update se7enxweb/exponential-platform-dxp-core`, then install again ([4.7](#47-the-130x-line)) |
| Bold Agency (`bold_eng`) shows the whole content tree (1.1.0.x) | the server files set its tree root to 2 | set `ngsite.bold_group.locations.tree_root.id: 386` |
| The 2.5 site answers `404` at `/` or the container reports a non-existent `tree_root.id` parameter | the tree root is 2 or not defined | `ngsite.default.locations.tree_root.id: 168` in `app/config/parameters.yml` ([4.3](#43-the-25-line-master-v250x)) |
| `attempt to write a readonly database` | the SQLite file or `var/` belongs to the user who ran the install | give `var/` and the file to the PHP-FPM user |
| HTTP 500, `Could not find the entry "..."` | the site's assets were not built | `yarn build:prod` |
| Admin interface without styles or blank | the admin assets were not built | the admin build of your line ([4.1](#41-overview-the-same-steps-on-every-line)) |
| `Unknown install type` | the type is not registered on this line or its data package is missing | `--help` lists the types; see the line's section |
| `JWT ... unable to load key` on REST calls | no key pair | `php bin/console lexik:jwt:generate-keypair` |
| Images missing on the 2.5 generation | the storage symlink into `ezpublish_legacy/` is missing (after a Composer update) | redo [step 4 of 4.3](#step-4-the-symlinks-into-the-legacy-kernel) |

[Chapter 13](13-troubleshooting.md) has the complete troubleshooting tables.

## 4.11 Running the install again

The install commands **drop and recreate every table they know**. On a database that already has tables, they ask
for confirmation (`--no-interaction` answers yes for you). Never point an install command at a production database:
there is no undo except your backup. To start over on SQLite, move the database file aside and run the install again;
to keep uploaded images, note that the installers skip copying the demo images when `public/var/site/storage` is not
empty.

## 4.12 Checklist

- [ ] The database exists with `utf8mb4` / `utf8mb4_unicode_520_ci` (MySQL, MariaDB) or is owned by its user
  (PostgreSQL); nothing to do for SQLite.
- [ ] `parameters.yml` (2.5 generation: the `env(DATABASE_*)` values, the tree root 168 for the CJW content) or
  `.env.local` (newer lines: `DATABASE_URL`, `APP_ENV`) has your values and a new secret.
- [ ] On 1.3.0.x, `se7enxweb/exponential-platform-dxp-core` is `v5.0.9` or newer before the install, and the
  `nglayouts_*` tables exist after it.
- [ ] The install command finished without an error and the search index was built.
- [ ] On the 2.5 generation the legacy symlinks exist.
- [ ] Site assets and admin assets are built; on 1.1.0.x to 1.3.0.x the JWT keys and the GraphQL schema exist.
- [ ] `var/`, the document root's `var/` and (where present) `ezpublish_legacy/var/` are writable by PHP-FPM, and the
  SQLite file belongs to it.
- [ ] The web server and the console use the same `APP_ENV`.
- [ ] You logged in and changed the `admin` password.
- [ ] Next: [chapter 6](06-serving-the-site.md) for the web server, [chapter 14](14-security-hardening.md) before going
  public.

## References

In this repository:

- The install guides of each line: `doc/INSTALL.md` on `1.0.0.x` and in the `v2.5.0.6` release, `doc/sevenx/INSTALL.md`
  on the 1.1.0.x, 1.2.0.x and 1.3.0.x branches; on `master`, [doc/INSTALL.md](../INSTALL.md) is the short guide.
- `app/config/parameters.yml.dist` and `default_parameters.yml` (2.5 generation), `config/app/server/` (newer lines),
  `assets/symlink/root_*/.htaccess` (the `.htaccess` that `ngsite:symlink:project` links into `public/`).
- [doc/netgen/INSTALL.md](../netgen/INSTALL.md): the upstream Media Site install notes (image variations, GraphQL,
  translations); [doc/netgen/LAUNCHPAD.md](../netgen/LAUNCHPAD.md) describes the upstream eZ Launchpad Docker setup for
  `netgen/media-site` and was not adapted to Nexus.
- The installer code: `src/AppBundle/Installer/` (1.0.0.x), `src/Command/` and `src/Installer/` (1.1.0.x),
  `src/RepositoryInstaller/` (1.2.0.x), `vendor/se7enxweb/exponential-platform-dxp-core/src/bundle/RepositoryInstaller/`
  (1.3.0.x).
- Chapters [5](05-the-demo-site-and-layouts.md), [6](06-serving-the-site.md), [7](07-databases.md),
  [9](09-frontend-and-themes.md), [13](13-troubleshooting.md), [14](14-security-hardening.md).

External:

- Upstream install procedures: [2.5](https://doc.ibexa.co/en/2.5/getting_started/install_ez_platform/),
  [3.3](https://doc.ibexa.co/en/3.3/getting_started/install_ez_platform/), [4.6](https://doc.ibexa.co/en/4.6/getting_started/install_ibexa_dxp/),
  [5.0](https://doc.ibexa.co/en/5.0/getting_started/install_ibexa_dxp/).
- Symfony: [configuration and `.env` files](https://symfony.com/doc/current/configuration.html),
  [file permissions](https://symfony.com/doc/current/setup/file_permissions.html),
  [Symfony CLI server](https://symfony.com/doc/current/setup/symfony_cli.html).
- Doctrine: [connection URLs](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#connecting-using-a-url).
- MySQL: [CREATE USER](https://dev.mysql.com/doc/refman/8.0/en/create-user.html); PostgreSQL:
  [schema privileges](https://www.postgresql.org/docs/current/ddl-schemas.html).
- The 1.3.0.x core package: [se7enxweb/exponential-platform-dxp-core on Packagist](https://packagist.org/packages/se7enxweb/exponential-platform-dxp-core)
  (source [se7enxweb/core](https://github.com/se7enxweb/core), the installer in `src/bundle/RepositoryInstaller/`).
- Netgen: [Media Site documentation](https://docs.netgen.io/projects/media-site/en/latest/),
  [Netgen Layouts installation into an existing project](https://docs.netgen.io/projects/layouts/en/latest/getting_started/install_existing_project.html).
- [The Exponential 6 book](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md) for the legacy
  kernel's own settings.

[Contents](README.md) · Previous: [3. Getting the code](03-getting-the-code.md) · Next: [5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md)
