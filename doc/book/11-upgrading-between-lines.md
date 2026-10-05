# 11. Upgrading between lines

Exponential Platform Nexus is four products in one repository: each line sits on its own platform generation, its own
Symfony major version and its own Netgen package set. Moving a site from one line to the next is therefore not a
`composer update`. It is the same kind of step as a platform generation change: new packages, a new project layout or
new configuration keys, and a database step. This chapter takes a site from 1.0.0.x to 1.1.0.x, then to 1.2.0.x, then
to 1.3.0.x, one step at a time. For each step it lists the package swaps, the Symfony upgrade, the database work (with
the SQL), the Netgen Layouts, Site API and Tags changes, the configuration renames, and what happens to the legacy
kernel. It ends with patch updates inside a line, verification, rollback and a checklist.

[Previous: 10. Operations](10-operations.md) · [Next: 12. Migrating into Nexus](12-migrating-into.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [The four lines side by side](#111-the-four-lines-side-by-side)
2. [How a line upgrade works](#112-how-a-line-upgrade-works)
3. [Before you start](#113-before-you-start)
4. [From 1.0.0.x to 1.1.0.x: Platform 2.5 to 3.3, Symfony 3.4 to 5.4](#114-from-100x-to-110x-platform-25-to-33-symfony-34-to-54)
5. [From 1.1.0.x to 1.2.0.x: Platform 3.3 to Ibexa OSS 4.6](#115-from-110x-to-120x-platform-33-to-ibexa-oss-46)
6. [From 1.2.0.x to 1.3.0.x: Ibexa OSS 4.6 to Platform v5, Symfony 7.4](#116-from-120x-to-130x-ibexa-oss-46-to-platform-v5-symfony-74)
7. [Netgen Layouts across the lines](#117-netgen-layouts-across-the-lines)
8. [The migration bundle across the lines](#118-the-migration-bundle-across-the-lines)
9. [Patch updates inside a line](#119-patch-updates-inside-a-line)
10. [Verify the upgrade](#1110-verify-the-upgrade)
11. [Rollback](#1111-rollback)
12. [Checklist](#1112-checklist)
13. [References](#1113-references)

Conventions: commands run from the project root. `USER`, `DATABASE` and the like are placeholders. Every version and
package name in this chapter was read from the `composer.json` (and, where the branch has one, the `composer.lock`) of
the branch named, on 5 October 2026. Old product names appear only where they name an upstream package or page.

---

## 11.1 The four lines side by side

| | 1.0.0.x (the 2.5 generation) | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|
| Branches and release tags | `master`: `v2.5.0.0` to `v2.5.0.7`, `1.0.0.8` and `1.0.0.10`; branch `1.0.0.x`: `v1.0.0.0.1` to `v1.0.0.0.3`, `1.0.0.4` to `1.0.0.7`, `1.0.0.9` and `1.0.0.11` | `1.1.0.x`: `v1.1.0.0` to `v1.1.0.8` | `1.2.0.x`: `v1.2.0.0`, `v1.2.0.1` | `1.3.0.x`: `1.3.0.0.0`, `1.3.0.1` to `1.3.0.6` |
| Platform | eZ Platform 2.5 (`se7enxweb/ezpublish-kernel ~7.5.40`) | Platform 3.3 (`se7enxweb/oss ~3.3.0`, `se7enxweb/ezplatform-kernel ~1.3`) | Ibexa OSS 4.6 (`se7enxweb/oss ~4.6.0`) | Platform v5 (`se7enxweb/exponential-platform-dxp`, locked kernel `se7enxweb/exponential-platform-dxp-core v5.0.7`) |
| Symfony | 3.4 (`se7enxweb/symfony v3.4.55`) | 5.4 | 5.4 | 7.4 |
| PHP (`composer.json`) | `^7.1.3 \|\| ... \|\| ^8.6`; in practice 8.1 or newer, because the legacy kernel requires `^8.1` | `^8.0` | `>=8.2` | `>=8.4` |
| Node.js | `.nvmrc` (added 2026-10-05): `v22` on `master`, `v20` on the branch `1.0.0.x`; no `engines`; Webpack 4 with `NODE_OPTIONS=--openssl-legacy-provider` in every script | `.nvmrc` `v18`, `engines` `^18 \|\| ^20` | `.nvmrc` `v18`, `engines` `^18` | `.nvmrc` `v22`, `engines` `^22` |
| Project layout | `app/`, `src/AppBundle/`, `web/` | `config/`, `src/` (`App\`), `templates/`, `public/` | as 1.1 | as 1.1 |
| Legacy kernel | yes: `se7enxweb/exponential ^6.0.12`, `se7enxweb/legacy-bridge ^2.1` | yes: `se7enxweb/legacy-bridge ^3.0`, `se7enxweb/site-legacy-bundle ^2.0` | yes: `se7enxweb/site-legacy-bundle v2.0.0`, which locks `se7enxweb/ibexa-legacy-bridge 4.x-dev` and `se7enxweb/exponential dev-main`; the legacy bundle is registered in `config/bundles.php` | **no** |
| Netgen Layouts | 1.4 (`se7enxweb/layouts-ezplatform ^1.4.11`) | 1.4 (`netgen/layouts-ezplatform ~1.4.0`) | 1.4 (`netgen/layouts-ibexa ~1.4.0`, locked `layouts-core 1.4.13`) | 2.0 (`netgen/layouts-ibexa ~2.0.0`, locked fork `se7enxweb/layouts-core`) |
| Netgen Site API | `netgen/ezplatform-site-api ^3.7` | `se7enxweb/ezplatform-site-api ~4.3` | `netgen/ibexa-site-api ^6.1.2` (locked 6.3.1) | `netgen/ibexa-site-api ^7.0` (locked 7.0.0) |
| Netgen Tags | `se7enxweb/tagsbundle ^3.4` | 4.x, through the site bundle (not required directly) | `netgen/tagsbundle` (locked 5.3.1) | `netgen/tagsbundle` (locked 6.0.2) |
| Site bundle | `se7enxweb/site-bundle ^1.7.4` | `se7enxweb/site-bundle ~2.1.5.1` | `se7enxweb/site-bundle ~3.0.6` | `se7enxweb/site-bundle 5.0.x-dev` |
| Demo data package | `master`: `se7enxweb/cjw-exponential-media-site-data ^1.0`; branch `1.0.0.x`: `netgen/media-site-data ~1.8.1` | `se7enxweb/media-site-data ~2.2.5.2` | `netgen/media-site-data ^3.3` | `netgen/media-site-data ^4.0` |
| Migration bundle | `se7enxweb/ezmigrationbundle ^5.9.4` | `se7enxweb/ezmigrationbundle ^6.0` | `tanoconsulting/ibexa-migration-bundle` (locked 1.0.5) | `mrk-te/ibexa-migration-bundle2` (locked 3.0.0) |
| Information collection | `netgen/information-collection-bundle ^1.9` | `se7enxweb/information-collection-bundle ^2.0` | `netgen/information-collection-bundle ^3.0@alpha` | `netgen/information-collection-bundle ^4.0@alpha` |

Two warnings about the tag list. First, the repository also carries the tags of the upstream Netgen media site it was
forked from (`1.0.0` to `3.1.6`, without a fourth position): those are not Nexus releases. Second, the 2.5
generation has two branches that share their history up to early 2026: `master`, which carries the `v2.5.0.x` tags
and the tags `1.0.0.8` and `1.0.0.10`, and the branch `1.0.0.x` with the older `1.0.0.x` tags, `1.0.0.9` and
`1.0.0.11`. Composer orders them as one series, so a constraint such as `~1.0.0.9` installed master code (`1.0.0.10`)
until `1.0.0.11` was tagged on 5 October 2026, and installs the `1.0.0.x` branch since; pin an exact version. Both run the same
Symfony 3.4 stack, kernel and legacy kernel; they differ in the demo content and its installer type
([chapter 4](04-installing.md)). In this chapter "1.0.0.x" means that generation, whichever of the two branches you
run. List the releases before choosing a target:

```bash
git fetch --tags
git tag -l --sort=version:refname | grep -E '^v?(1\.[0-3]\.0\.|2\.5\.0\.)'
gh release list --repo se7enxweb/exponential-platform-nexus --limit 30
```

A plain lexical sort puts `1.0.0.10` before `1.0.0.4`; always use `--sort=version:refname` or `sort -V`.

## 11.2 How a line upgrade works

Every line is a separate project skeleton, so the work has the same shape each time:

1. **Bring the site to the newest release of its current line** ([11.9](#119-patch-updates-inside-a-line)), and make
   sure it runs cleanly there. The upgrade steps assume the last schema state of the source line.
2. **Create a new project from the next line**, on a copy of the server or a new one, without running the installer:

   ```bash
   git clone -b 1.1.0.x https://github.com/se7enxweb/exponential-platform-nexus.git site-next
   cd site-next
   git checkout v1.1.0.8            # the newest tag of the target line
   ```

3. **Carry over your own work**: your bundles and controllers, templates, translations, front-end sources,
   configuration under the line's configuration directory, the storage directory, and your `.env.local` or
   `parameters.yml` values. The demo configuration of the target line (siteaccess names, host maps, image variations)
   is the target's, not yours; merge yours into it rather than copying files wholesale.
4. **Install the dependencies** with `composer install`, so that Composer resolves exactly what the target line
   resolves, then require your own extra packages on top.
5. **Point the project at a copy of your database** and run the database steps of this chapter on that copy.
6. **Rebuild** caches, assets and the search index, and verify ([11.10](#1110-verify-the-upgrade)).

Never run the installer of the target line (`ezplatform:install`, `exponential:install`) against the database you
are upgrading: it creates a new repository and overwrites content. The demo data packages are for new installations
only.

Go one line at a time. A site on 1.0.0.x that wants 1.3.0.x goes through 1.1.0.x and 1.2.0.x, because the database
steps are written for consecutive generations. The step can be done on a staging copy and repeated as often as needed;
only the last run, on a fresh copy of production during an editorial freeze, counts.

## 11.3 Before you start

Take stock on a copy of the production database. MySQL syntax is shown; the statements are plain SQL.

```sql
-- Which release the database says it is (2.5 writes ezpublish-version; 3.0 and later write ezplatform-release)
SELECT name, value FROM ezsite_data;

-- Netgen Layouts: which schema migrations have run
SELECT version FROM nglayouts_migration_versions ORDER BY version;

-- The migration bundle: which project migrations have run
SELECT migration, status FROM kaliop_migrations ORDER BY execution_date;

-- Password hashes of types 1 to 5 stop working at 3.0 (see 11.4)
SELECT password_hash_type, COUNT(*) FROM ezuser GROUP BY password_hash_type;
```

Back up the database and the binary storage, and restore the backup once on a scratch machine before you start:

```bash
mysqldump -u USER -p --single-transaction DATABASE > before-upgrade.sql   # MySQL or MariaDB
pg_dump -U USER DATABASE > before-upgrade.sql                             # PostgreSQL
tar czf storage-before-upgrade.tgz web/var ezpublish_legacy/var           # 1.0.0.x
tar czf storage-before-upgrade.tgz public/var                             # 1.1.0.x and later
```

On 1.0.0.x and 1.1.0.x the legacy kernel keeps its own `var/` directory under `ezpublish_legacy/`; back it up with
the rest. For SQLite databases, see [chapter 7](07-databases.md): no upstream upgrade script exists for SQLite, so a
generation change is done on MySQL or PostgreSQL and the result converted back if you want SQLite again.

The vendor's rules for the database scripts apply to every step below: back up first, clear the caches afterwards,
and never pass `--force` to `mysql` or `psql`. A failing statement must stop the run so you can fix its cause.

## 11.4 From 1.0.0.x to 1.1.0.x: Platform 2.5 to 3.3, Symfony 3.4 to 5.4

This is the largest step: the platform generation, the Symfony major version (two of them) and the project layout all
change at once.

### Packages

| 1.0.0.x | 1.1.0.x |
|---|---|
| `se7enxweb/ezpublish-kernel ~7.5.40` and about twenty separate `se7enxweb/ezplatform-*` packages | `se7enxweb/oss ~3.3.0` (the metapackage) with `se7enxweb/ezplatform-kernel ~1.3`, `se7enxweb/ezplatform-rest ~1.3.27`, `se7enxweb/ezplatform-content-forms ~1.3.19`, `se7enxweb/ezplatform-http-cache ~2.3.19`, `se7enxweb/ezplatform-richtext ~2.3.28` |
| `se7enxweb/symfony v3.4.55` | `se7enxweb/symfony 5.4.x-dev`, `symfony/framework-bundle 5.4.*`, `symfony/runtime`, `symfony/dotenv`, Flex (`se7enxweb/symfony-flex ^1.22`) |
| `se7enxweb/legacy-bridge ^2.1`, `se7enxweb/exponential ^6.0.12` | `se7enxweb/legacy-bridge ^3.0`, `se7enxweb/site-legacy-bundle ^2.0` |
| `se7enxweb/layouts-ezplatform ^1.4.11` and the `se7enxweb/layouts-ezplatform-*` integrations | `netgen/layouts-ezplatform ~1.4.0` and the `netgen/layouts-ezplatform-*` integrations |
| `netgen/ezplatform-site-api ^3.7` | `se7enxweb/ezplatform-site-api ~4.3` |
| `se7enxweb/site-bundle ^1.7.4`, `se7enxweb/admin-ui-bundle ^2.9.15` | `se7enxweb/site-bundle ~2.1.5.1`, `se7enxweb/admin-ui-bundle ^3.0` |
| `se7enxweb/ezmigrationbundle ^5.9.4` | `se7enxweb/ezmigrationbundle ^6.0` |
| `netgen/information-collection-bundle ^1.9` | `se7enxweb/information-collection-bundle ^2.0` |
| `se7enxweb/cjw-exponential-media-site-data ^1.0` | `se7enxweb/media-site-data ~2.2.5.2` (new installations only) |

### Project layout and configuration

| 1.0.0.x | 1.1.0.x |
|---|---|
| `app/AppKernel.php` registers bundles | `config/bundles.php` |
| `app/config/*.yml` | `config/packages/*.yaml`, project configuration under `config/app/` |
| `app/config/parameters.yml` (from `parameters.yml.dist`), values as `env(...)` parameters | `.env`, overridden by `.env.local` and real environment variables |
| `SYMFONY_ENV`, `SYMFONY_DEBUG`, `SYMFONY_SECRET` | `APP_ENV`, `APP_DEBUG`, `APP_SECRET` |
| `SYMFONY_TRUSTED_PROXIES`, read by `web/app.php` | `TRUSTED_PROXIES`, read by `framework.trusted_proxies` in `config/packages/ezpublish.yaml` (since `v1.1.0.8`, 2026-10-05; add it yourself on `v1.1.0.7`, [chapter 14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies)) |
| `SYMFONY_HTTP_CACHE` (on by default outside `dev`), the project's `app/AppCache.php` | `APP_HTTP_CACHE` (off by default; read by `public/index.php` since `v1.1.0.8`), the platform's `AppCache` |
| `DATABASE_DRIVER`, `DATABASE_HOST`, `DATABASE_NAME`, ... | `DATABASE_URL` with `serverVersion` (plus `DATABASE_CHARSET`, `DATABASE_COLLATION`; `DATABASE_VERSION` is in `.env` but nothing reads it) |
| `web/` document root, front controller `web/app.php` | `public/`, front controller `public/index.php` (Symfony Runtime) |
| `src/AppBundle/` (`AppBundle\`), templates in `src/AppBundle/Resources/views/` | `src/` (`App\`), templates in `templates/` |
| Front-end sources in `src/AppBundle/Resources/es6` and `sass` | `assets/` |
| Logs in `var/logs/` | `var/log/` |
| `ezpublish:` configuration key | still `ezpublish:` in `config/packages/ezpublish.yaml` and `config/app/packages/ezpublish_siteaccess.yaml` |

Move your own configuration file by file into the new places, and translate parameters into environment variables.
Chapter [8](08-configuration.md) describes where each line keeps what.

The demo siteaccesses differ: the 1.0.0.x demo configuration lists `de`, `en`, `ngadminui`, `admin` and
`legacy_admin`, the 1.1.0.x one `fh_eng`, `bold_eng`, `bold_ger`, the admin siteaccess `%ngsite.admin_siteaccess_name%`
(`adminui`), `ngadminui` and `legacy_admin`. Keep your own siteaccess list; take only the structure from the target.

### Symfony 3.4 to 5.4

Symfony does not skip majors: read [UPGRADE-4.0](https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md) and
[UPGRADE-5.0](https://github.com/symfony/symfony/blob/5.4/UPGRADE-5.0.md) and the
[major version upgrade guide](https://symfony.com/doc/current/setup/upgrade_major.html) for your own code. The changes
custom code meets most: bundles as services with autowiring, no `ContainerAwareCommand`, `Kernel` in `src/`, the
`templating` component gone (Twig only), `security.yml` to the new authenticators later in 5.x, and event names as
classes. The platform side of the same step (signal slots to events, the `ezplatform:` key, the field type SPI as an
abstract class, dynamic settings removed) is in the vendor's [adapt code to v3](https://doc.ibexa.co/en/latest/update_and_migration/from_2.5/update_from_2.5/)
pages and summarised in [chapter 16.5.5 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1655-namespaces-bundles-and-configuration-keys).

### Database

Run, in this order, on the copy:

1. **2.5 to 3.0.** `upgrade/db/mysql/ezplatform-2.5.latest-to-3.0.0.sql` (or `upgrade/db/postgresql/...`) from
   [`se7enxweb/exponential-platform`, branch 3.2](https://github.com/se7enxweb/exponential-platform/tree/3.2/upgrade/db).
   It replaces the version row in `ezsite_data` with `ezplatform-release` = `3.0.0`, widens
   `ezcontentclass_attribute.data_text1`, adds `ezcontentclass_attribute.is_thumbnail`, adds
   `ezkeyword_attribute_link.version` (filled from the current versions) and sets the user login pattern. The full
   statements are quoted in [chapter 16.5.2 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1652-bring-the-source-to-the-last-release-of-its-line).

   ```bash
   mysql -u USER -p DATABASE < ezplatform-2.5.latest-to-3.0.0.sql
   ```

2. **3.2.3 to 3.2.4.** `upgrade/db/mysql/ezplatform-3.2.3-to-3.2.4.sql` from the same directory.
3. **3.3 patches.** The statements the vendor lists for 3.3.2, 3.3.7 (`ibexa_setting`), 3.3.25 and 3.3.34 on
   [Update from v3.3.x to v3.3.latest](https://doc.ibexa.co/en/latest/update_and_migration/from_3.3/update_from_3.3/).
   The open source edition has no `ibexa/installer`, so you take the SQL from that page.
4. **Do not drop the legacy tables.** The vendor's 3.0 notes list about 80 tables of the old schema (shop, workflow,
   collaboration, information collection, RSS, ...) that a 3.x site may drop. Nexus 1.1.0.x still runs the legacy
   kernel through the bridge (the `legacy_admin` and `ngadminui` siteaccesses), and the legacy kernel needs them. Keep
   every table.
5. **The keyword link column.** The MySQL 2.5 to 3.0 script adds `ezkeyword_attribute_link.version` as `NOT NULL`
   without a default. The legacy kernel inserts keyword links without that column and then fails in strict SQL mode.
   Give it a default while the legacy kernel is in use:

   ```sql
   ALTER TABLE ezkeyword_attribute_link MODIFY version INT(11) NOT NULL DEFAULT 0;   -- MySQL, MariaDB
   ALTER TABLE ezkeyword_attribute_link ALTER COLUMN version SET DEFAULT 0;           -- PostgreSQL
   ```

6. **Netgen Layouts**: no schema change (1.4 on both lines). Check the state with the migrations file that matches
   the installed Doctrine Migrations version ([11.7](#117-netgen-layouts-across-the-lines)).
7. **Netgen Tags 3 to 4**: no SQL. Service `ezpublish.api.service.tags` is gone (use `eztags.api.service.tags`), tag
   ids are integers, the Varnish header `X-Tag-ID` became `xkey` tags
   ([TagsBundle UPGRADE](https://github.com/netgen/TagsBundle/blob/master/doc/UPGRADE.md)).
8. **Password hashes.** 3.0 removed the MD5 hash types 1, 2, 3 and plain text (5). Users whose hash is of those types
   cannot sign in on 1.1.0.x and must reset their password. Find them with the query in [11.3](#113-before-you-start).

### Netgen Site API 3 to 4

The 3.5 to 4.0 notes ([upgrade_350_400](https://github.com/netgen/ezplatform-site-api/blob/master/docs/upgrades/upgrade_350_400.rst))
apply: `RenderContentEvent` is removed in favour of `RenderViewEvent`, `RelationService::loadFieldRelation(s)` take a
Content object instead of an id, the view fallback without a sub-request is on by default, and every setting moves
under `ng_site_api` (for example `ng_fallback_to_secondary_content_view` becomes `fallback_to_secondary_content_view`).
Named objects `location` and `tag` became `locations` and `tags`.

### Legacy kernel

The legacy kernel stays. The bridge moves from 2.1 to 3.x, whose commands carry the primary name
`exponential:legacy:*` and keep `ezpublish:legacy:*` as aliases, so the `ezpublish:legacy:*` lines in the 1.1.0.x
`composer.json` scripts keep working. Your legacy extensions and settings move from `src/AppBundle/ezpublish_legacy/`
to the location the 1.1.0.x project uses for them (the `ngsite:symlink:legacy` and
`ezpublish:legacybundles:install_extensions` scripts link them into `ezpublish_legacy/`).

### Front end

The site theme moves from `src/AppBundle/Resources/` to `assets/`, Webpack 4 with Encore 0.27 to the 1.1.0.x
toolchain, and Node.js to version 18 or 20 (`.nvmrc` says `v18`). The admin interface assets are built with the
translation dump and `yarn ez` (`webpack.config.ez.js`), which `make ibexa-assets` runs since `v1.1.0.8`. See
[chapter 9](09-frontend-and-themes.md).

## 11.5 From 1.1.0.x to 1.2.0.x: Platform 3.3 to Ibexa OSS 4.6

Symfony stays at 5.4, but the platform renames almost everything: namespaces, configuration keys, Twig functions,
service names and the Netgen Layouts integration packages.

### Packages

| 1.1.0.x | 1.2.0.x |
|---|---|
| `se7enxweb/oss ~3.3.0` | `se7enxweb/oss ~4.6.0`; `conflict` with `ibexa/core <4.6.10` |
| PHP `^8.0` | PHP `>=8.2` |
| `netgen/layouts-ezplatform`, `-site-api`, `-relation-list-query`, `-tags-query` `~1.4.0` | `netgen/layouts-ibexa`, `netgen/layouts-ibexa-site-api`, `netgen/layouts-ibexa-relation-list-query`, `netgen/layouts-ibexa-tags-query` `~1.4.0` |
| `se7enxweb/ezplatform-site-api ~4.3`, `se7enxweb/ezplatform-search-extra ~2.6` | `netgen/ibexa-site-api ^6.1.2`, `netgen/ibexa-search-extra ^3.2.1` |
| `se7enxweb/site-bundle ~2.1.5.1`, `se7enxweb/admin-ui-bundle ^3.0` | `se7enxweb/site-bundle ~3.0.6`, `se7enxweb/admin-ui-bundle ^4.0` |
| `se7enxweb/legacy-bridge ^3.0`, `se7enxweb/site-legacy-bundle ^2.0` | `se7enxweb/site-legacy-bundle v2.0.0` (locks `se7enxweb/ibexa-legacy-bridge 4.x-dev`) |
| `se7enxweb/ezmigrationbundle ^6.0` | `tanoconsulting/ibexa-migration-bundle` |
| `se7enxweb/information-collection-bundle ^2.0` | `netgen/information-collection-bundle ^3.0@alpha` (no stable release for 4.x exists) |
| | new: `netgen/ibexa-fieldtype-enhanced-link`, `netgen/ibexa-admin-ui-extra`, `netgen/toolbar`, `netgen/ibexa-scheduled-visibility` |

### Configuration renames

The configuration files of the two branches show the 4.0 renames directly:

| 1.1.0.x | 1.2.0.x |
|---|---|
| `config/packages/ezpublish.yaml` (`ezpublish:`) | `config/packages/ibexa.yaml` (`ibexa:`) |
| `config/app/packages/ezpublish_siteaccess.yaml` | `config/app/packages/ibexa_siteaccess.yaml` |
| `ezplatform_admin_ui.yaml`, `ezplatform_assets.yaml`, `ezplatform_solr.yaml`, `ezrichtext.yaml`, `ez_doctrine_schema.yaml`, `jms_translation.yaml`, `fos_http_cache.yaml` | `ibexa_admin_ui.yaml`, `ibexa_assets.yaml`, `ibexa_solr.yaml`, `ibexa_richtext.yaml`, `ibexa_doctrine_schema.yaml`, `ibexa_jms_translation.yaml`, `ibexa_http_cache.yaml` |
| `webpack.config.ez.js`, `ez.webpack.config.manager.js`, Yarn script `ez` | `webpack.config.ibexa.js`, `ibexa.webpack.config.manager.js`, Yarn script `ibexa` |
| `Netgen\Bundle\LayoutsEzPlatform*Bundle` in `config/bundles.php` | `Netgen\Bundle\LayoutsIbexa*Bundle` |
| `Kaliop\eZMigrationBundle\EzMigrationBundle` | `Kaliop\eZMigrationBundle\eZMigrationBundle` |

The rest of the 4.0 renames (PHP namespaces `eZ\Publish\API\...` to `Ibexa\Contracts\Core\...`, the config resolver
namespace `ezsettings` to `ibexa.site_access.config`, configuration keys such as `ezdesign` to `ibexa_design_engine`,
Twig functions, the back office moving to `ibexa-` CSS classes) are the vendor's; the full list is on the
[4.0 update page](https://doc.ibexa.co/en/latest/update_and_migration/from_3.3/to_4.0/) and in
[chapter 16.5.5 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1655-namespaces-bundles-and-configuration-keys).
Console command names change with the kernel each line installs. On 1.1.0.x the 3.3 kernel fork
`se7enxweb/ezplatform-kernel` (tags `v1.3.43` to `v1.3.45`) registers `exponential:*` primary names and keeps
`ibexa:*` and `ezplatform:*` as deprecated aliases. The lock file of 1.2.0.x installs the upstream 4.6 kernel
`ibexa/core` (from `github.com/ibexa/core`), whose commands are `ibexa:*` with `ezplatform:*` aliases; the 4.6 branch
of the se7enxweb core fork that renames them is not what this line installs. Both lines add their own
`exponential:install` and an `exponential:reindex` proxy. So cron entries and scripts that use `ibexa:*` or
`ezplatform:*` keep working on both lines; scripts that use other `exponential:*` kernel commands written on 1.1.0.x
need the `ibexa:` name on 1.2.0.x ([chapter 10.1](10-operations.md#101-the-operators-map-per-line)).

The trusted proxy setting moves with the `framework:` block: on 1.1.0.x (since `v1.1.0.8`) it is in
`config/packages/ezpublish.yaml`, on 1.2.0.x in `config/packages/framework.yaml`; keep `TRUSTED_PROXIES` in
`.env.local` and check that the target's file reads it ([chapter 14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies)).

### Database

1. **3.3 to 4.0, open source edition.** The vendor's [3.3 to 4.0 page](https://doc.ibexa.co/en/latest/update_and_migration/from_3.3/to_4.0/)
   gives one statement for the open source edition:

   ```sql
   ALTER TABLE ezcontentclassgroup ADD COLUMN is_system BOOLEAN NOT NULL DEFAULT false;
   ```

2. **4.x minor steps.** For the open source edition 4.1 to 4.4 need no SQL; 4.5 creates `ibexa_token_type` and
   `ibexa_token`, and 4.6 adds `ibexa_token.revoked`. The statements are on the vendor's update pages per minor
   version (listed in [chapter 16.5.2 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1652-bring-the-source-to-the-last-release-of-its-line));
   the commercial migrations (`ibexa:migrations:*`, taxonomy, product catalog) do not apply.
3. **Netgen Layouts identifiers.** The Layouts integration was renamed from eZ Platform to Ibexa, and with it the
   identifiers stored in the Layouts tables. Netgen's
   [migration page](https://docs.netgen.io/projects/layouts/en/1.4/getting_started/migrate_ezplatform_ibexa.html)
   gives these statements; run all of them:

   ```sql
   UPDATE nglayouts_block SET definition_identifier = 'ibexa_content_field' WHERE definition_identifier = 'ezcontent_field';
   UPDATE nglayouts_block SET view_type = 'ibexa_content_field' WHERE definition_identifier = 'ibexa_content_field' AND view_type = 'ezcontent_field';
   UPDATE nglayouts_collection_item SET value_type = 'ibexa_location' WHERE value_type = 'ezlocation';
   UPDATE nglayouts_collection_item SET value_type = 'ibexa_content' WHERE value_type = 'ezcontent';
   UPDATE nglayouts_collection_query SET type = 'ibexa_content_search' WHERE type = 'ezcontent_search';
   UPDATE nglayouts_rule_condition SET type = 'ibexa_site_access' WHERE type = 'ez_site_access';
   UPDATE nglayouts_rule_condition SET type = 'ibexa_site_access_group' WHERE type = 'ez_site_access_group';
   UPDATE nglayouts_rule_condition SET type = 'ibexa_content_type' WHERE type = 'ez_content_type';
   UPDATE nglayouts_rule_target SET type = 'ibexa_location' WHERE type = 'ez_location';
   UPDATE nglayouts_rule_target SET type = 'ibexa_content' WHERE type = 'ez_content';
   UPDATE nglayouts_rule_target SET type = 'ibexa_children' WHERE type = 'ez_children';
   UPDATE nglayouts_rule_target SET type = 'ibexa_subtree' WHERE type = 'ez_subtree';
   UPDATE nglayouts_rule_target SET type = 'ibexa_semantic_path_info' WHERE type = 'ez_semantic_path_info';
   UPDATE nglayouts_rule_target SET type = 'ibexa_semantic_path_info_prefix' WHERE type = 'ez_semantic_path_info_prefix';
   ```

   Then look for anything with an `ez` prefix that the list does not cover (for example a query type of the tags
   query or of your own integration):

   ```sql
   SELECT DISTINCT type FROM nglayouts_collection_query;
   SELECT DISTINCT type FROM nglayouts_rule_target;
   SELECT DISTINCT type FROM nglayouts_rule_condition;
   SELECT DISTINCT value_type FROM nglayouts_collection_item;
   ```

   A type that no registered handler knows makes the layout resolver or the block fail at run time; map it to the
   name the `layouts-ibexa-*` package registers before you go live.

4. **Netgen Tags 4 to 5**: no SQL. Services are prefixed `netgen_tags.`, container parameters move from `eztags` to
   `netgen_tags`, the route `eztags_tag_url` became `netgen_tags.tag.url` and the Twig global `eztags_admin` became
   `netgen_tags_admin` ([TagsBundle UPGRADE](https://github.com/netgen/TagsBundle/blob/master/doc/UPGRADE.md)).
5. **Site API 4 to 6.** The package is `netgen/ibexa-site-api` from here on. The one upgrade guide Netgen publishes
   for this range ([5.4 to 6.0](https://docs.netgen.io/projects/site-api/en/latest/upgrades/upgrade_540_600.html))
   changes `Location::$path` into a Path object; the old array of ids is `Location::$pathArray`. Review your templates
   and code for `.path` uses.

### Legacy kernel

It stays on 1.2.0.x: `config/bundles.php` registers `eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle` and
`Netgen\Bundle\SiteLegacyBundle\NetgenSiteLegacyBundle`, the lock file installs `se7enxweb/ibexa-legacy-bridge` and
`se7enxweb/exponential`, and the demo siteaccess list still has `ngadminui` and `legacy_admin`. Keep the legacy tables
as on 1.1.0.x.

### Front end

Node.js 18 (`.nvmrc` `v18`, `engines` `^18`). The admin interface build is `yarn ibexa`
(`composer ibexa-assets` runs the translation dump and that build).

## 11.6 From 1.2.0.x to 1.3.0.x: Ibexa OSS 4.6 to Platform v5, Symfony 7.4

The last step changes the Symfony major version twice (5.4 to 7.4), renames every core table, moves Netgen Layouts to
2.0, and removes the legacy kernel.

### Packages

| 1.2.0.x | 1.3.0.x |
|---|---|
| `se7enxweb/oss ~4.6.0` | `se7enxweb/exponential-platform-dxp` (`dev-master`; the lock file has `se7enxweb/exponential-platform-dxp-core v5.0.7`) |
| PHP `>=8.2`, Symfony 5.4 | PHP `>=8.4`, Symfony `7.4.*`, `symfony/flex ^2` |
| `netgen/layouts-* ~1.4.0` | `netgen/layouts-* ~2.0.0`, with the fork `se7enxweb/layouts-core` installed through the metapackage |
| `netgen/ibexa-site-api ^6.1.2`, `netgen/ibexa-search-extra ^3.2.1` | `netgen/ibexa-site-api ^7.0`, `netgen/ibexa-search-extra ^4.0` |
| `netgen/tagsbundle` 5.x | `netgen/tagsbundle` 6.x |
| `se7enxweb/site-bundle ~3.0.6` | `se7enxweb/site-bundle 5.0.x-dev` |
| `tanoconsulting/ibexa-migration-bundle` | `mrk-te/ibexa-migration-bundle2` (`Kaliop\IbexaMigrationBundle\KaliopMigrationBundle`) |
| `netgen/information-collection-bundle ^3.0@alpha` | `^4.0@alpha` |
| `netgen/ibexa-fieldtype-enhanced-link ^1.1.2`, `netgen/ibexa-admin-ui-extra ^1.3.0`, `netgen/toolbar ^1.0`, `netgen/ibexa-scheduled-visibility ^1.0` | `^2.0` each |
| `se7enxweb/site-legacy-bundle v2.0.0`, `se7enxweb/admin-ui-bundle ^4.0` | removed |

Do not require the forks (`se7enxweb/layouts-core`, `se7enxweb/fieldtype-richtext`) yourself on 1.3.0.x: the
metapackage requires them. If a `composer update` ever installs the upstream `netgen/layouts-core` instead, the site
fails with `Call to undefined method ...Parameter::isEmpty()` ([chapter 13](13-troubleshooting.md)).

### Symfony 5.4 to 7.4

Read [UPGRADE-6.0](https://github.com/symfony/symfony/blob/6.4/UPGRADE-6.0.md) and
[UPGRADE-7.0](https://github.com/symfony/symfony/blob/7.4/UPGRADE-7.0.md) and remove the deprecations on 5.4 first,
as the [major version upgrade guide](https://symfony.com/doc/current/setup/upgrade_major.html) says. What custom code
meets most: native return types on overridden methods, annotations replaced by PHP attributes (routes, Doctrine
mapping, validation), the old security system removed, `Request::get()` deprecated, and the `ContainerAware`
helpers gone. The vendor's 5.0 page asks for `ibexa/rector` to rewrite platform class names and for annotations to
be switched to attributes ([update to 5.0](https://doc.ibexa.co/en/latest/update_and_migration/from_4.6/update_to_5.0/)).

### Database

1. **Bring the source to 4.6.latest** (the vendor names 4.6.32) on 1.2.0.x first.
2. **Rename the core tables.** The vendor's `ibexa-4.6.latest-to-5.0.0.sql` renames every core table to `ibexa_*`
   and the columns `contentclass_id` and `contentclassattribute_id` with them (`ezcontentobject` becomes
   `ibexa_content`, `ezcontentclass` becomes `ibexa_content_type`, and so on). The open source edition has no
   `ibexa/installer`; the vendor page prints the statements. The complete rename map is in
   [chapter 16.5.6 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1656-the-database-generation-by-generation);
   errors about missing commercial tables (`ezpage_*`, `ezform_*`, `ezeditorialworkflow_*`) can be ignored on the open
   source edition, as the vendor says.
3. **Field type identifiers.** 5.0 renames the field type identifiers (`ezstring` to `ibexa_string`, `ezrichtext` to
   `ibexa_richtext`, 28 in all). The old ones stay supported; field templates and your migration files should use the
   new names (`{% block ezstring_field %}` becomes `{% block ibexa_string_field %}`).
4. **Netgen Layouts 1.4 to 2.0**: no schema migration. The last Layouts migration is `Version010300` in both the
   1.4.13 package and the 2.0 fork (compared file by file); `nglayouts_migration_versions` therefore stays as it is.
   The Layouts identifiers already carry the `ibexa_` names from [11.5](#115-from-110x-to-120x-platform-33-to-ibexa-oss-46).
5. **Netgen Tags 5 to 6**: no SQL; PHP 8.3 and Ibexa 5.0 are the requirements. The `eztags*` tables keep their names
   in a 5.0 database.
6. **Legacy tables.** Tables that only the legacy kernel used (shop, workflow, collaboration, legacy information
   collection, RSS and the like) have no counterpart in 5.0. The rename script leaves them alone; nothing on 1.3.0.x
   reads them. Drop them only after the archive copy of the database is safe, or keep them if you might move to
   Exponential Platform Legacy v5 later.
7. **Persistence cache.** From 5.0.7 `cache:clear` no longer clears the persistence cache pool: run
   `php bin/console cache:pool:clear <pool>` (the pool named by `CACHE_POOL`, `cache.tagaware.filesystem` by default)
   after the database steps.

### Removing the legacy kernel

1.3.0.x is a single-kernel Symfony 7.4 application. What disappears, compared with 1.2.0.x:

- `eZ\Bundle\EzPublishLegacyBundle\EzPublishLegacyBundle` and `Netgen\Bundle\SiteLegacyBundle\NetgenSiteLegacyBundle`
  from `config/bundles.php`;
- the `ezpublish:legacy:assets_install`, `ezpublish:legacybundles:install_extensions`, `ngsite:symlink:legacy` and
  `ezpublish:legacy:script bin/php/ezpgenerateautoloads.php` lines from the `composer.json` scripts, and the
  `extra.ezpublish-legacy-dir` entry;
- the `ezpublish_legacy/` directory and the legacy cron jobs (the `ngsite.cron` file of 1.0.0.x runs
  `ezpublish:legacy:script runcronjobs.php`; remove any such entry from your crontab);
- the `ngadminui` and `legacy_admin` siteaccesses: the 1.3.0.x list is `fh_eng`, `bold_eng`, `bold_ger` and
  `%ngsite.admin_siteaccess_name%` (`adminui`). Remove them from your own configuration and from the web server's
  host map, and send their old URLs somewhere useful.

Legacy extensions, TPL templates and INI settings have no place in 1.3.0.x. Port what you still need to Symfony
bundles and Twig before the step, or stay on 1.2.0.x. If you need the legacy kernel next to Platform v5, the product
for that is Exponential Platform Legacy `v5.0.x` (legacy bridge 5.x), described in
[chapter 16.6.8 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1668-keeping-a-symfony-stack-next-to-the-legacy-kernel).

### Console commands

On the v5 kernel the renamed commands exist only as `exponential:*` (`exponential:reindex`,
`exponential:urls:regenerate-aliases`, `exponential:user:validate-password-hashes`, ...); `ibexa:reindex` and the
other old kernel names are not registered. Commands of packages that were not forked keep their upstream names
(`ibexa:cron:run`, `ibexa:graphql:generate-schema`). Rewrite cron entries and deployment scripts:

```bash
grep -rnE "bin/console +(ibexa|ezplatform|ezpublish):" /etc/cron.d deploy deploy.php 2>/dev/null
```

### Netgen Layouts 2.0 in custom code

Netgen's [1.4 to 2.0 notes](https://docs.netgen.io/projects/layouts/en/latest/upgrades/upgrade_140_200.html): PHP 8.4
and Symfony 7.3 at least; `Symfony\Component\Uid\Uuid` replaces Ramsey UUID; getters became properties
(`->value`, `isEmpty`); `$status` is the enum `Netgen\Layouts\API\Values\Status`; `LayoutResolverService::loadRules()`
became `loadRulesFromGroup()` / `loadRulesForLayout()` and `loadCondition()` became `loadRuleCondition()`; in Twig
`nglayouts_item_path(item, 'admin')` became `nglayouts_item_admin_path(item)`; the Content Browser goes to 2.0 too.
Custom block definitions, query types and value types need these changes.

After every `composer update` on 1.3.0.x, check that Flex did not unconfigure the Layouts bundles when the package
switched between `netgen/layouts-core` and `se7enxweb/layouts-core`:

```bash
git diff config/bundles.php config/routes/
```

`NetgenLayoutsBundle`, `NetgenLayoutsAdminBundle` and `config/routes/netgen_layouts.yaml` must still be there.

### Front end and secrets

Node.js 22 (`.nvmrc` `v22`, `engines` `^22`); the admin interface is built with `yarn ibexa:build` from
`@ibexa/frontend-config`, the site theme with `yarn build:prod` (`webpack.config.project.js`). The 1.3.0.x `.env`
ships `APP_SECRET=` empty: set a value before the first request ([chapter 14](14-security-hardening.md)).

## 11.7 Netgen Layouts across the lines

| Line | Layouts | Integration packages | Migrations command |
|---|---|---|---|
| 1.0.0.x | 1.4 | `se7enxweb/layouts-ezplatform*` | Doctrine Migrations 2 (`doctrine/migrations ^2.3.1`): `--configuration=vendor/netgen/layouts-core/migrations/doctrine2.yaml` |
| 1.1.0.x | 1.4 | `netgen/layouts-ezplatform*` | the branch has no lock file: check `composer show doctrine/migrations`; with 3.x use `--configuration=vendor/netgen/layouts-core/migrations/doctrine.yaml` |
| 1.2.0.x | 1.4 | `netgen/layouts-ibexa*` (identifier SQL in [11.5](#115-from-110x-to-120x-platform-33-to-ibexa-oss-46)) | Doctrine Migrations 3 (locked 3.4.3): `doctrine.yaml` as above |
| 1.3.0.x | 2.0 | `netgen/layouts-ibexa* ~2.0.0`, fork `se7enxweb/layouts-core` | `--configuration=vendor/se7enxweb/layouts-core/migrations/doctrine.yaml` |

```bash
composer show doctrine/migrations          # 2.x or 3.x decides which file to use
php bin/console doctrine:migrations:status  --configuration=vendor/netgen/layouts-core/migrations/doctrine.yaml
php bin/console doctrine:migrations:migrate --configuration=vendor/netgen/layouts-core/migrations/doctrine.yaml
```

If the command fails with "unrecognized configuration keys", the file belongs to the other Doctrine Migrations version.
The Layouts migrations abort on anything but MySQL ("Migration can only be executed safely on MySQL."): on PostgreSQL
and SQLite the Layouts tables come from the installer's schema, and a schema change has to be applied by hand. The
migration table is `nglayouts_migration_versions` on every line.

## 11.8 The migration bundle across the lines

The project migrations (content types, roles, sections) are kept by the Kaliop migration bundle and its successors.
Every line uses the `kaliop:migration:*` commands and the `kaliop_migrations` table, so the record of what has run
carries over:

| Line | Package | Bundle class |
|---|---|---|
| 1.0.0.x | `se7enxweb/ezmigrationbundle ^5.9.4` | `Kaliop\eZMigrationBundle\EzMigrationBundle`, **not registered** in `app/AppKernel.php` as shipped: add `new Kaliop\eZMigrationBundle\EzMigrationBundle()` to the bundle list before you use the commands |
| 1.1.0.x | `se7enxweb/ezmigrationbundle ^6.0` | `Kaliop\eZMigrationBundle\EzMigrationBundle` |
| 1.2.0.x | `tanoconsulting/ibexa-migration-bundle` | `Kaliop\eZMigrationBundle\eZMigrationBundle` |
| 1.3.0.x | `mrk-te/ibexa-migration-bundle2` | `Kaliop\IbexaMigrationBundle\KaliopMigrationBundle` |

```bash
php bin/console kaliop:migration:status
php bin/console kaliop:migration:migrate
```

Migration files written for an older generation may name things the new one renamed: field type identifiers on 5.0,
service names, Layouts value types. Run `kaliop:migration:status` on the upgraded copy and review the files that
have not run yet before you run them. There is no rollback: the way back is the database backup.

## 11.9 Patch updates inside a line

Inside a line the steps are small:

```bash
git fetch --tags
git tag -l --sort=version:refname | grep '^v\?1\.3\.0\.'        # the line you run
git merge <newest-tag-of-your-line>                              # or rebase your project on it
composer install                                                 # what the line's lock file or constraints resolve
php bin/console doctrine:migrations:migrate --allow-no-migration # the project's own Doctrine migrations
php bin/console kaliop:migration:migrate                         # project content migrations, if any
php bin/console cache:clear
```

Then rebuild the front end if `package.json` or the theme changed ([chapter 9](09-frontend-and-themes.md)) and, on
1.3.0.x, check `config/bundles.php` and `config/routes/` as in [11.6](#116-from-120x-to-130x-ibexa-oss-46-to-platform-v5-symfony-74).
On 1.0.0.x and 1.1.0.x the legacy kernel is a Composer package installed into `ezpublish_legacy/`; a Composer run
replaces that directory, so anything placed there by hand (rather than by the `ngsite:symlink:*` and
`ezpublish:legacybundles:install_extensions` scripts) is lost. Platform patch releases may carry SQL of their own;
read the release notes of the forks for the versions you pass.

**Fixes in the project's own files.** `composer update` brings the forks' fixes, but files of the project itself
(`web/app.php`, `app/AppCache.php`, `public/index.php`, `config/`, `Makefile`, `deploy/`) change only when you merge.
On 5 October 2026 every branch received such fixes, released the same day as `v2.5.0.7`, `1.0.0.11`, `v1.1.0.8`,
`v1.2.0.1` and `1.3.0.6` (the security ones are listed in [chapter 14](14-security-hardening.md), the operational
ones in [chapter 10](10-operations.md)). A project created from an older tag does not get them by `composer update`.
To see what your project lacks compared with the newest release or the head of its line, and to take one file:

```bash
git fetch origin
git log --oneline <your-tag>..origin/1.3.0.x -- public config Makefile deploy   # the line you run
git diff <your-tag> origin/1.3.0.x -- public/index.php                          # one file
git checkout origin/1.3.0.x -- public/index.php                                 # take it
```

Review each diff before you take it: the branch head may also carry changes you do not want yet.

## 11.10 Verify the upgrade

```sql
SELECT name, value FROM ezsite_data;                  -- 3.x/4.x: ezplatform-release
SELECT name, value FROM ibexa_site_data;              -- 5.0
SELECT COUNT(*) FROM ezcontentobject WHERE status = 1; -- the same number before and after (ibexa_content on 5.0)
SELECT COUNT(*) FROM nglayouts_layout WHERE status = 1;
SELECT COUNT(*) FROM nglayouts_rule WHERE status = 1;
```

```bash
php bin/console debug:container --env=prod > /dev/null && echo container-ok
php bin/console exponential:reindex        # 1.1.0.x and later; ezplatform:reindex on 1.0.0.x
curl -s -o /dev/null -w '%{http_code}\n' https://example.com/
curl -s -o /dev/null -w '%{http_code}\n' https://example.com/adminui/
```

Then by hand: the front page and a page of every content type you use, a page with a layout, the Layouts editor
(`/nglayouts/admin` below the admin siteaccess, for example `/adminui/nglayouts/admin` on 1.1.0.x and later), an image variation of an old image, a search, a form submission (information collection), and,
on 1.0.0.x to 1.2.0.x, the legacy admin.

## 11.11 Rollback

Keep the old project directory and the database backup untouched until the new line has run in production for a
while. A rollback is: point the web server back at the old project, restore the database dump into a fresh database,
restore the storage archive. Content created after the cut-over is lost in a rollback; that is the reason for the
editorial freeze. Do not try to run the old line against an upgraded database: every step above changes the schema in
ways the older kernel does not expect.

## 11.12 Checklist

- [ ] Newest release of the current line installed and running.
- [ ] Database, storage and (1.0.0.x, 1.1.0.x) `ezpublish_legacy/var` backed up, and the backup restored once.
- [ ] Target project created from the next line's newest tag; own code, configuration and storage carried over.
- [ ] Database steps of this chapter run on a copy, without `--force`.
- [ ] Layouts identifiers renamed (to 1.2.0.x) and checked for leftovers.
- [ ] Legacy tables kept while the legacy kernel is in use (up to 1.2.0.x); keyword link default set.
- [ ] Password hashes of types 1 to 5 found and their users told to reset (to 1.1.0.x).
- [ ] Cron entries and deployment scripts use the command names of the target line.
- [ ] `config/bundles.php` and `config/routes/` checked after every Composer run (1.3.0.x).
- [ ] Caches cleared, assets built, search reindexed, verification of [11.10](#1110-verify-the-upgrade) done.

## 11.13 References

In this repository, per branch (read them with `git show <branch>:<path>`):

- `composer.json` and `composer.lock` of `1.0.0.x` (and `master`), `1.1.0.x`, `1.2.0.x`, `1.3.0.x`
- `config/bundles.php` (1.1.0.x and later), `app/AppKernel.php` (1.0.0.x)
- `doc/sevenx/INSTALL.md` on `1.1.0.x`, `1.2.0.x`, `1.3.0.x`: the per-line installation and operations notes,
  including "Updating the Codebase" and the Flex recipe hazard (1.3.0.x)
- [doc/netgen/IBEXA_MIGRATIONS.md](../netgen/IBEXA_MIGRATIONS.md): the migration bundle in day-to-day use
- [UPGRADE.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/UPGRADE.md)

Exponential:

- [Exponential book, chapter 16: Migrating from eZ Platform and Ibexa](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md):
  the generation steps, the SQL per version, the 5.0 rename map, command names
- [Exponential book, chapter 11: Upgrading](https://github.com/se7enxweb/exponential/blob/main/doc/install/11-upgrading.md):
  the legacy kernel's own update files
- [se7enxweb/exponential-platform, upgrade/db](https://github.com/se7enxweb/exponential-platform/tree/3.2/upgrade/db)

Symfony and PHP:

- [Upgrading a major version](https://symfony.com/doc/current/setup/upgrade_major.html)
- [UPGRADE-4.0](https://github.com/symfony/symfony/blob/4.4/UPGRADE-4.0.md),
  [UPGRADE-5.0](https://github.com/symfony/symfony/blob/5.4/UPGRADE-5.0.md),
  [UPGRADE-6.0](https://github.com/symfony/symfony/blob/6.4/UPGRADE-6.0.md),
  [UPGRADE-7.0](https://github.com/symfony/symfony/blob/7.4/UPGRADE-7.0.md)
- PHP migration guides: [8.1](https://www.php.net/manual/en/migration81.php),
  [8.2](https://www.php.net/manual/en/migration82.php), [8.3](https://www.php.net/manual/en/migration83.php),
  [8.4](https://www.php.net/manual/en/migration84.php), [8.5](https://www.php.net/manual/en/migration85.php)

Platform (vendor pages, for the upstream steps):

- [Update from v2.5](https://doc.ibexa.co/en/latest/update_and_migration/from_2.5/update_from_2.5/),
  [to v3.3](https://doc.ibexa.co/en/latest/update_and_migration/from_2.5/to_3.3/),
  [v3.3 to v3.3.latest](https://doc.ibexa.co/en/latest/update_and_migration/from_3.3/update_from_3.3/)
- [v3.3 to v4.0](https://doc.ibexa.co/en/latest/update_and_migration/from_3.3/to_4.0/),
  [v4.6 to v4.6.latest](https://doc.ibexa.co/en/latest/update_and_migration/from_4.6/update_from_4.6/)
- [v4.6 to v5.0](https://doc.ibexa.co/en/latest/update_and_migration/from_4.6/update_to_5.0/)

Netgen:

- [Netgen Layouts upgrades](https://docs.netgen.io/projects/layouts/en/latest/upgrades/index.html),
  [1.4 to 2.0](https://docs.netgen.io/projects/layouts/en/latest/upgrades/upgrade_140_200.html),
  [eZ Platform to Ibexa](https://docs.netgen.io/projects/layouts/en/1.4/getting_started/migrate_ezplatform_ibexa.html)
- [Site API upgrades](https://docs.netgen.io/projects/site-api/en/latest/upgrades/index.html),
  [3.5 to 4.0](https://github.com/netgen/ezplatform-site-api/blob/master/docs/upgrades/upgrade_350_400.rst)
- [TagsBundle UPGRADE](https://github.com/netgen/TagsBundle/blob/master/doc/UPGRADE.md)
- [Information Collection bundle](https://github.com/netgen/NetgenInformationCollectionBundle)
- Migration bundles: [se7enxweb/ezmigrationbundle](https://github.com/se7enxweb/ezmigrationbundle),
  [tanoconsulting/ibexa-migration-bundle](https://github.com/tanoconsulting/ibexa-migration-bundle),
  [mrk-te/ibexa-migration-bundle2](https://github.com/mrk-te/ibexa-migration-bundle2)
