# Installing Exponential Platform Nexus: the short guide

This page gets a working Exponential Platform Nexus installation with the demo site on any of the four lines, in the
fewest steps that are safe. Every step links to the chapter of [the book](book/README.md) that explains it, lists
the options and says what can go wrong. For a production site, read the book's chapters on serving the site
([6](book/06-serving-the-site.md)) and security hardening ([14](book/14-security-hardening.md)) before going live.

## 1. Pick a line

| Line | Branch | Platform | Symfony | PHP | Legacy kernel | Node.js |
|---|---|---|---|---|---|---|
| 2.5 generation (1.0.0.x) | `master` (this branch), also `1.0.0.x` | eZ Platform 2.5 | 3.4 | 8.1 or newer | yes | 22 (`.nvmrc`); the scripts set `NODE_OPTIONS=--openssl-legacy-provider` |
| 1.1.0.x | `1.1.0.x` | Platform 3.3 | 5.4 | 8.0 or newer | yes | 18 or 20 |
| 1.2.0.x | `1.2.0.x` | Ibexa OSS 4.6 | 5.4 | 8.2 or newer | yes | 18 |
| 1.3.0.x | `1.3.0.x` | Platform v5 | 7.4 | 8.4 or newer | no | 22 |

A new project without legacy code should start on 1.3.0.x. Choose an older line when you need the legacy kernel
(Exponential 6) beside the Symfony stack, or when you move a site of that platform generation into Nexus.
Details: [chapter 1](book/01-introduction.md), [chapter 2](book/02-requirements.md).

## 2. Requirements at a glance

- **PHP** at the version of your line, with at least `ctype`, `curl`, `dom`, `fileinfo`, `gd` (or `imagick` in
  addition), `iconv`, `intl`, `mbstring`, `opcache`, `pdo` with the driver of your database (`pdo_mysql`,
  `pdo_pgsql`, or `pdo_sqlite` and `sqlite3`), `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `xsl` and
  `zip`; `apcu` for the 2.5 generation's configuration as shipped; `date.timezone` set. The complete list per line,
  with the reason for each extension, is in [chapter 2](book/02-requirements.md).
- **A database**: MySQL or MariaDB with `utf8mb4`, PostgreSQL, or SQLite ([chapter 7](book/07-databases.md)).
- **Composer 2**, **Node.js** at the version above and **Yarn 1** (or npm).
- **A web server**: Exponential Velocity (recommended), or Apache or nginx with PHP-FPM
  ([chapter 6](book/06-serving-the-site.md)).

## 3. Create the database

MySQL or MariaDB:

```sql
CREATE DATABASE nexus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
CREATE USER 'nexus'@'localhost' IDENTIFIED BY 'change-me';
GRANT ALL PRIVILEGES ON nexus.* TO 'nexus'@'localhost';
```

PostgreSQL (make the application user the owner; on PostgreSQL 15 and later a plain `GRANT` on the database does not
allow it to create tables):

```bash
sudo -u postgres psql -c "CREATE USER nexus WITH PASSWORD 'change-me';"
sudo -u postgres psql -c "CREATE DATABASE nexus OWNER nexus ENCODING 'UTF8';"
```

SQLite needs no server; the file is named in the configuration ([chapter 7](book/07-databases.md)).

## 4. Get the code

```bash
git clone -b 1.3.0.x https://github.com/se7enxweb/exponential-platform-nexus.git nexus   # or master, 1.1.0.x, 1.2.0.x
cd nexus
git checkout 1.3.0.5            # the newest tag of the line: git tag -l --sort=version:refname
composer install
```

Run Composer with the same PHP version the site will use. The Composer scripts publish assets and, on the lines with
the legacy kernel, install it into `ezpublish_legacy/` and link the project's legacy files into it. Details and the
`composer create-project` alternative: [chapter 3](book/03-getting-the-code.md).

## 5. Install

### The 2.5 generation (`master`)

1. Edit `app/config/parameters.yml` (created from `parameters.yml.dist` by `composer install`): the `env(DATABASE_*)`
   values and `env(SYMFONY_SECRET)`. Generate the secret with `openssl rand -hex 32`; the shipped placeholder is public.
2. Install the demo content:

   ```bash
   php bin/console ezplatform:install cjw-exponential-media
   ```

   `cjw-exponential-media` installs the CJW demo ("JAC Example", German and English) from the package
   `se7enxweb/cjw-exponential-media-site-data`. The kernel also offers `exponential-oss`, a clean repository without
   demo content. The `1.0.0.x` branch uses other types ([chapter 4](book/04-installing.md)).
3. Build the site theme (Webpack 4; the npm scripts set the OpenSSL option current Node.js needs):

   ```bash
   npm install
   npm run build:prod      # or: yarn install && yarn build:prod
   ```

4. Link the project's legacy files into the legacy kernel, which Composer replaces on every update:

   ```bash
   ln -s ../../src/AppBundle/ezpublish_legacy/extension/app ezpublish_legacy/extension/app
   mv ezpublish_legacy/var/site/storage ezpublish_legacy/var/site/storage-empty
   ln -s ../../../src/AppBundle/ezpublish_legacy/var/site/storage ezpublish_legacy/var/site/storage
   ln -s ../../src/AppBundle/Resources/public web/bundles/app
   ```

   A link that already exists was made by a Composer script; leave it. Repeat the storage link after every
   `composer update` that updates `se7enxweb/exponential`.
5. Make `var/`, `web/var/` and `ezpublish_legacy/var/` writable for the web server's user, and clear the cache as that
   user (not as root):

   ```bash
   sudo setfacl -R  -m u:www-data:rwX -m u:$(whoami):rwX var web/var ezpublish_legacy/var
   sudo setfacl -dR -m u:www-data:rwX -m u:$(whoami):rwX var web/var ezpublish_legacy/var
   sudo -u www-data php bin/console cache:clear --env=prod
   ```

6. Replace the demo host names in `app/config/ezplatform_siteaccess.yml` (`Map\Host`) and `app/config/http_cache.yml`
   with yours. The siteaccesses are `de` (default), `en`, `admin`, `ngadminui` and `legacy_admin`.

The document root is `web/` and the front controller `web/app.php`. Full walk-through: [chapter 4](book/04-installing.md).

### 1.1.0.x, 1.2.0.x and 1.3.0.x

1. Create `.env.local` with at least:

   ```dotenv
   APP_ENV=prod
   APP_SECRET=<output of: openssl rand -hex 32>
   DATABASE_URL="mysql://nexus:change-me@127.0.0.1:3306/nexus?serverVersion=8.0&charset=utf8mb4"
   JWT_PASSPHRASE=<another random value>
   ```

   The shipped `.env` sets `APP_ENV=dev` and placeholder secrets (an empty `APP_SECRET` on 1.3.0.x). On SQLite,
   `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"` and
   `MESSENGER_TRANSPORT_DSN=sync://` ([chapter 7](book/07-databases.md)).
2. Install the demo content:

   ```bash
   php bin/console exponential:install exponential-media --no-interaction
   ```

3. Build and generate:

   ```bash
   nvm use                                         # Node.js from .nvmrc
   yarn install
   yarn build:prod                                 # the site theme
   php bin/console assets:install --symlink --relative public
   yarn ez                                         # admin assets on 1.1.0.x; 1.2.0.x and 1.3.0.x: composer ibexa-assets
   php bin/console lexik:jwt:generate-keypair
   php bin/console ibexa:graphql:generate-schema
   php bin/console cache:clear
   ```

4. On 1.3.0.x, check that Composer's recipes did not remove the Netgen Layouts bundles:
   `git diff config/bundles.php config/routes/` must show no removed `NetgenLayouts*` lines.
5. Make `var/` and `public/var/` writable for the web server's user, as above.

The document root is `public/`. The siteaccesses are `fh_eng` (default), `bold_eng`, `bold_ger` and the admin
siteaccess `adminui` (`/adminui/`); 1.1.0.x and 1.2.0.x also have `ngadminui` and `legacy_admin`. Full walk-through
per line: [chapter 4](book/04-installing.md).

## 6. Serve the site

- **Exponential Velocity**, the recommended way: one process that serves HTTP and HTTPS and runs PHP itself
  ([chapter 6](book/06-serving-the-site.md)).
- **Apache or nginx with PHP-FPM**: examples in [doc/apache2](apache2/) and [doc/nginx](nginx/); `netgen-site*` for
  the 2.5 generation (`web/`), `media-site*` for the later lines (`public/`). Adapt them as chapter 6 describes.

For a quick look on a development machine, `symfony server:start` serves `public/` on the later lines.

## 7. First login and the next steps

- Sign in to the administration interface as `admin` with the password the project README gives for the demo data,
  and **change it at once**.
- Before going live: production environment, real secrets, admin access restricted, trusted proxies, HTTP cache
  rules, security headers ([chapter 14](book/14-security-hardening.md) and its checklist).
- Cron, workers, caches, backups: [chapter 10](book/10-operations.md).

## 8. When something goes wrong

| Symptom | Where to look |
|---|---|
| Composer refuses the PHP version | Run Composer with the site's PHP; [chapter 13.2](book/13-troubleshooting.md#132-composer-and-dependencies) |
| `EntrypointNotFoundException ... "photoswipe-init"` | The site theme was not built; [chapter 13.3](book/13-troubleshooting.md#133-front-end-build) |
| `ERR_OSSL_EVP_UNSUPPORTED` during the build (2.5 generation) | Use the npm scripts, which set the OpenSSL option; [chapter 13.3](book/13-troubleshooting.md#133-front-end-build) |
| `Unable to create the store directory` | `var/` not writable for the web server; [chapter 13.8](book/13-troubleshooting.md#138-files-and-permissions) |
| `Container extension "netgen_layouts" is not registered` (1.3.0.x) | Restore `config/bundles.php`; [chapter 13.6](book/13-troubleshooting.md#136-netgen-layouts) |
| Images missing after `composer update` (2.5 generation) | Recreate the storage link of step 4; [chapter 13.2](book/13-troubleshooting.md#132-composer-and-dependencies) |
| Anything else | The log: `var/logs/prod.log` (2.5 generation) or `var/log/prod.log`; then [chapter 13](book/13-troubleshooting.md) |

## 9. More

- [The book](book/README.md): every chapter, and which ones you need
- [Upgrading between lines](book/11-upgrading-between-lines.md), [migrating a site into Nexus](book/12-migrating-into.md)
- Issues: <https://github.com/se7enxweb/exponential-platform-nexus/issues>; security problems privately, as
  [SECURITY.md](../SECURITY.md) says
- License: [LICENSE.md](../LICENSE.md)
