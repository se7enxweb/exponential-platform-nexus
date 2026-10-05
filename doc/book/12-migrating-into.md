# 12. Migrating into Nexus

This chapter brings an existing site into Exponential Platform Nexus: from eZ Publish 4.x and 5.x, from eZ Platform
1.x to 3.x, from Ibexa DXP or Ibexa OSS 3.3 to 5.0, from Exponential Platform Legacy and from Exponential 6. Most of
the platform work is the same as on any Exponential Platform distribution and is described in depth in the
Exponential book; this chapter tells you which of its chapters applies to your source, which Nexus line is the
target, and then covers what is specific to Nexus: the demo site you do not want, the site bundle's location
parameters, the siteaccess names, moving page structure into Netgen Layouts, and moving layouts between
installations.

[Previous: 11. Upgrading between lines](11-upgrading-between-lines.md) · [Next: 13. Troubleshooting](13-troubleshooting.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [Where the detailed steps are](#121-where-the-detailed-steps-are)
2. [Pick the target line](#122-pick-the-target-line)
3. [The order of work](#123-the-order-of-work)
4. [From eZ Publish 4.x and Exponential 6](#124-from-ez-publish-4x-and-exponential-6)
5. [From eZ Publish 5.x](#125-from-ez-publish-5x)
6. [From eZ Platform and Ibexa](#126-from-ez-platform-and-ibexa)
7. [From Exponential Platform Legacy](#127-from-exponential-platform-legacy)
8. [Starting from Nexus without its demo content](#128-starting-from-nexus-without-its-demo-content)
9. [The site bundle and the media site structure](#129-the-site-bundle-and-the-media-site-structure)
10. [Moving page structure into Netgen Layouts](#1210-moving-page-structure-into-netgen-layouts)
11. [Moving layouts between installations](#1211-moving-layouts-between-installations)
12. [Verify, roll back, checklist](#1212-verify-roll-back-checklist)
13. [References](#1213-references)

Old product names appear only where they identify the system you migrate from.

---

## 12.1 Where the detailed steps are

The Exponential book (repository `se7enxweb/exponential`, directory `doc/install/`) covers every source system point
by point against the vendor's own migration pages. Read the chapter for your source first; this chapter does not
repeat it.

| Your source | Exponential book chapter | What it gives you |
|---|---|---|
| eZ Publish 3.10, 4.0 to 4.7 | [14. Migrating from the 4.x line](https://github.com/se7enxweb/exponential/blob/main/doc/install/14-migrating-from-4x.md) | The database step by step to the Exponential 6 schema, files and cluster, settings, porting extensions to PHP 8, passwords, search |
| eZ Publish 5.x (Symfony stack plus legacy) | [15. Migrating from the 5.x legacy stack](https://github.com/se7enxweb/exponential/blob/main/doc/install/15-migrating-from-5x-legacy.md) | Which variant you run, the shared database, field types against datatypes, image aliases, REST, caches |
| eZ Platform 1.x to 3.x, Ibexa DXP or OSS 3.3 to 5.0 | [16. Migrating from eZ Platform and Ibexa](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md), path A | The package swap to the se7enxweb forks, command names, namespaces, the database per generation with the SQL, Page Builder against Netgen Layouts, porting custom bundles |
| Any of them | [17. Migration reference](https://github.com/se7enxweb/exponential/blob/main/doc/install/17-migration-reference.md) | Tables, datatypes and field types, INI and YAML, TPL and Twig, PHP API, password hashes, tooling |
| Exponential 6.0.x itself | [11. Upgrading](https://github.com/se7enxweb/exponential/blob/main/doc/install/11-upgrading.md) | The update files from any 6.0.x to today |

## 12.2 Pick the target line

The rule is the same as in [chapter 11](11-upgrading-between-lines.md): **same generation first**. Move into the Nexus
line that matches the platform generation your database is on, settle there, and use chapter 11 for the generation
changes afterwards.

| Your source | First target | Why |
|---|---|---|
| eZ Publish 4.x, Exponential 6, Exponential Platform Legacy 2.5 | 1.0.0.x | The 2.5 kernel works on the legacy schema, and 1.0.0.x runs the Exponential 6 kernel next to it on the same database |
| eZ Publish 5.x | 1.0.0.x, after the vendor's 5.x to 2.5 path | Same reason; the 5.x Symfony stack has its successor on 2.5 |
| eZ Platform 1.x | 1.0.0.x, after updating to 2.5 | The vendor's 1.x to 2.5 database scripts come first |
| eZ Platform 2.5 | 1.0.0.x | Same generation, a package swap |
| eZ Platform 3.0 to 3.2, Ibexa 3.3 | 1.1.0.x, after 3.3.latest | Same generation |
| Ibexa 4.0 to 4.6 | 1.2.0.x, after 4.6.latest | Same generation |
| Ibexa 5.0 | 1.3.0.x (PHP 8.4 or newer) | Same generation |
| Exponential Platform Legacy 3.3 (`v3.3.44.x`), 4.6 (`v4.6.23.x`), v5 (`v5.0.x`) | 1.1.0.x, 1.2.0.x, 1.3.0.x | Same generation; on v5 the legacy half is left behind ([12.7](#127-from-exponential-platform-legacy)) |

Lines 1.0.0.x to 1.2.0.x run the legacy kernel through the bridge; 1.3.0.x does not. If your editors work in the
legacy admin, or your site depends on legacy extensions, the legacy-capable lines keep that working while you port.

## 12.3 The order of work

1. **Inventory** the source: release in `ezsite_data`, field types in use, password hash types, languages, sizes, the
   bundles or extensions, siteaccesses, image variations, search engine, HTTP cache, cron, IO handler, PHP versions.
   The queries are in [chapter 16.4 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#164-before-you-start-inventory-freeze-and-backups).
2. **Freeze and back up** database and storage; restore the backup once on a scratch machine.
3. **Bring the source to the last release of its line** with the vendor's steps (or, for legacy sources, to the
   Exponential 6 schema).
4. **Create the Nexus project** from the target line's newest tag ([12.8](#128-starting-from-nexus-without-its-demo-content)),
   without running its installer against your database.
5. **Carry over** your code, templates, configuration and storage; point the project at a copy of your database.
6. **Add what Nexus needs on top of your schema**: the Netgen Layouts tables, the site bundle's location parameters
   and siteaccess structure ([12.9](#129-the-site-bundle-and-the-media-site-structure)).
7. **Build** caches, assets and the search index; **verify**; repeat on a fresh copy for the cut-over.

## 12.4 From eZ Publish 4.x and Exponential 6

A legacy-only site has no Symfony stack yet. The path is:

1. **Bring the database to the Exponential 6 schema** with chapter
   [14](https://github.com/se7enxweb/exponential/blob/main/doc/install/14-migrating-from-4x.md) of the Exponential book
   (from 4.x) or chapter [11](https://github.com/se7enxweb/exponential/blob/main/doc/install/11-upgrading.md) (from an
   older 6.0.x). Port your extensions to PHP 8 there; Nexus 1.0.0.x installs `se7enxweb/exponential ^6.0.12`, which
   resolves to the newest tag, `v6.0.14`, whose `composer.json` requires PHP `^8.1`.

   That chapter checks the result against the kernel's reference schema with `bin/php/ezsqldiff.php`. Mind the
   argument order, which the Exponential book now spells out: the tool prints the SQL that turns the **second**
   schema into the **first**, so the reference schema comes first and your database second:

   ```bash
   cd ezpublish_legacy     # or the Exponential 6 installation you migrate with
   php bin/php/ezsqldiff.php --type=mysql --host=HOST --user=USER --password=PASSWORD share/db_schema.dba DATABASE > to-6.0.sql
   grep -c '^CREATE TABLE' to-6.0.sql; grep '^DROP TABLE' to-6.0.sql
   ```

   The other way round, the output is a list of `DROP TABLE` statements for the legacy tables. Read the file before
   you run it (`mysql -u USER -p DATABASE < to-6.0.sql`, never with `--force`).
2. **Add the tables the 2.5 kernel expects** that a legacy-only database lacks. The 2.5 schema is the legacy one plus a
   few tables of the Symfony stack (`ezcontentclass_attribute_ml`, `eznotification`, `ezgmaplocation`, the comments
   and star rating tables). Compare the table list of your database with `data/mysql/schema.sql` of the
   `se7enxweb/ezpublish-kernel` 7.5 package the project installs, and create the missing ones from that file. The
   comparison of the schemas per generation is in
   [chapter 16.6.1 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1661-what-the-two-share).
3. **Create the Nexus 1.0.0.x project**, put your legacy extensions and siteaccess settings where the project keeps
   them (`src/AppBundle/ezpublish_legacy/`, linked into `ezpublish_legacy/` by the `ngsite:symlink:legacy` and
   `ezpublish:legacybundles:install_extensions` scripts), and copy the storage to `ezpublish_legacy/var/<site>/storage`
   or the directory your `VarDir` names. Point `app/config/parameters.yml` at the database.
4. **Keep the legacy siteaccesses running** through the bridge (`legacy_mode: true`) while you build the Twig side:
   editors keep the legacy admin, and the public siteaccess moves to Twig templates and Netgen Layouts one content
   type at a time.
5. **Field types**: XmlText stays XmlText on 1.0.0.x (`se7enxweb/ezplatform-xmltext-fieldtype v1.8.11` is in the
   project). Converting it to RichText is needed before 1.1.0.x only if you want to edit it in the Symfony admin;
   the vendor's converter command (`ezxmltext:convert-to-richtext`) is part of that package.
6. **Users and passwords**: the 2.5 kernel accepts the legacy hash types; types 1 to 5 stop working at 3.0
   ([chapter 11](11-upgrading-between-lines.md)). Ask those users to reset their passwords before you move on to 1.1.0.x.

## 12.5 From eZ Publish 5.x

Follow chapter [15 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/15-migrating-from-5x-legacy.md)
to find out which variant you run and which half carries the site. For Nexus the target is 1.0.0.x, which has both
halves again: the legacy part goes to `ezpublish_legacy/` as in [12.4](#124-from-ez-publish-4x-and-exponential-6), the
Symfony part becomes `src/AppBundle` code on the 2.5 kernel. The vendor's path from 5.4 to 2.5 (sort fields, the Page
field, landing page drafts) is summarised in
[chapter 16.5.11 of the Exponential book](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#16511-notes-per-source-version).

## 12.6 From eZ Platform and Ibexa

This is path A of chapter 16 of the Exponential book, which a Nexus migration follows step for step: bring the source
to the last release of its line (16.5.2), swap the upstream packages for the se7enxweb forks of the same generation
(16.5.3), fix console command names (16.5.4), and keep the database as it is, since the generation does not change.
What Nexus adds:

- **Netgen packages of the line.** Your project gains Netgen Layouts, the Site API, Tags and the site bundle at the
  versions of the target line ([chapter 11.1](11-upgrading-between-lines.md#111-the-four-lines-side-by-side)). If your
  source already used Netgen packages, keep their data: the `nglayouts_*`, `eztags*` and information collection
  tables move with the database. If your source used the eZ Platform names of the Layouts integration and you land
  on 1.2.0.x or 1.3.0.x, run the identifier SQL of [chapter 11.5](11-upgrading-between-lines.md#115-from-110x-to-120x-platform-33-to-ibexa-oss-46).
- **No Page Builder.** Nexus is built on the open source edition. Landing pages (`ezlandingpage`, `ezpage_*` tables),
  Form Builder content and the editorial workflow have no counterpart; rebuild landing pages as layouts
  ([12.10](#1210-moving-page-structure-into-netgen-layouts)) and export form submissions before the move.
- **Twig templates.** Your templates keep working on the same generation. Nexus adds its own themes and design
  engine configuration; decide per siteaccess whether it uses your templates, the Nexus themes, or a mix
  ([chapter 9](09-frontend-and-themes.md)).
- **The legacy tables, when you land on 1.1.0.x or 1.2.0.x.** These lines run the legacy kernel through the bridge
  (`legacy_admin`, `ngadminui`), and the legacy kernel needs the tables that the vendor's 3.0 notes allow a 3.x or
  4.x site to drop (shop, workflow, collaboration, information collection, RSS and the rest, about 80). If your
  source dropped them, or was installed from a 3.x or 4.x schema that never had them, recreate them before the first
  request to a legacy siteaccess. The same comparison as in path B of the Exponential book
  ([chapter 16.6.4](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1664-the-database-step-by-step))
  does it, reference schema first:

  ```bash
  cd ezpublish_legacy
  php bin/php/ezsqldiff.php --type=mysql --host=HOST --user=USER --password=PASSWORD share/db_schema.dba DATABASE > ../var/legacy-tables.sql
  grep -c '^CREATE TABLE' ../var/legacy-tables.sql      # large on a 3.x or 4.x database: the dropped tables
  ```

  Keep the `CREATE TABLE` and `ALTER TABLE ... ADD` statements; delete every `DROP TABLE` and `DROP COLUMN` line,
  which would remove what the Symfony stack needs (`ezcontentclass_attribute_ml`, `ibexa_*`, `is_thumbnail`,
  `password_updated_at`, ...); read the changed column definitions one by one (a statement that makes a column
  narrower than the data in it fails in strict mode or cuts values). Then give
  `ezkeyword_attribute_link.version` a default ([chapter 11.4](11-upgrading-between-lines.md#114-from-100x-to-110x-platform-25-to-33-symfony-34-to-54)).
  `ezsqldiff.php` speaks MySQL and PostgreSQL (`--type=postgresql`), not SQLite.
- **Console command names.** On 1.1.0.x the kernel fork's commands are `exponential:*` with the old names as
  aliases; on 1.2.0.x the locked upstream kernel keeps `ibexa:*`; both projects add `exponential:install` and an
  `exponential:reindex` proxy; on 1.3.0.x the renamed kernel commands exist only as `exponential:*`
  ([chapter 10.1](10-operations.md#101-the-operators-map-per-line), and chapter 16.5.4 of the Exponential book).
  Rewrite cron entries and deployment scripts for the target line.
- **The admin siteaccess** of lines 1.1.0.x to 1.3.0.x is named by the parameter `ngsite.admin_siteaccess_name`
  (`adminui`); if your source used another name (`admin` is common), either set the parameter to your name or update
  every link, host map and role assignment that names the siteaccess.

## 12.7 From Exponential Platform Legacy

Exponential Platform Legacy and Nexus share the platform generations and the legacy bridge; Nexus adds the Netgen
media site, Layouts and the Netgen bundles. From `v2.5.0.x`, `v3.3.44.x` and `v4.6.23.x` go to 1.0.0.x, 1.1.0.x and
1.2.0.x respectively, keep the legacy part as it is, and add the Netgen packages, the Layouts tables and the site
bundle's configuration. From `v5.0.x` (legacy bridge 5.x) the same-generation target is 1.3.0.x, which has no legacy
kernel: port what the legacy half did to Symfony first, or stay on Exponential Platform Legacy and add Netgen Layouts
there yourself.

## 12.8 Starting from Nexus without its demo content

A Nexus line brings a demo site. When you migrate, you want its code and packages, not its demo content:

```bash
git clone -b 1.2.0.x https://github.com/se7enxweb/exponential-platform-nexus.git site
cd site
git checkout v1.2.0.0
composer install
```

Then, before the first request against your database:

- **Do not run the installer** (`ezplatform:install` on 1.0.0.x, `exponential:install` on the later lines) against
  the migrated database. The installer types (`cjw-exponential-media` on `master`, `exponential-cjw` and the
  `netgen-media` types on the `1.0.0.x` branch, `exponential-media` on 1.1.0.x and later, `exponential-oss` and the
  upstream `clean` / `ibexa-oss`) create a repository from scratch.
- **The demo data package** (`se7enxweb/cjw-exponential-media-site-data` on `master`, `netgen/media-site-data` on the
  `1.0.0.x` branch, `media-site-data` on the later lines) can stay installed; it is only read by the installer.
- **Demo host maps.** The 1.0.0.x `app/config/ezplatform_siteaccess.yml` maps the demo host names of the project's
  own servers to siteaccesses. Replace that map with your hosts; leaving another site's host names in your
  configuration is harmless only until someone points DNS at your server.
- **The Netgen Layouts tables.** A database that never had Layouts gets them from the Layouts migrations
  (MySQL; [chapter 11.7](11-upgrading-between-lines.md#117-netgen-layouts-across-the-lines)) or, on PostgreSQL and
  SQLite, from the schema file the Layouts package ships (`vendor/netgen/layouts-core/resources/data/schema.mysql.sql`
  is the MySQL form; translate it for your engine). Run the migrations once on an empty set of tables and they create
  everything from `Version000700` on.
- **Other bundle tables** (information collection, migration bundle, Tags if your source had no eztags extension):
  each bundle documents its schema; `php bin/console doctrine:schema:update --dump-sql` shows what Doctrine-mapped
  bundles expect, without changing anything.

## 12.9 The site bundle and the media site structure

The Netgen site bundle (forked as `se7enxweb/site-bundle`) expects a content tree shaped like the media site, and its
configuration names locations by id. After pointing Nexus at your database, go through these parameters:

| Parameter | Lines | What it must point at |
|---|---|---|
| `ngsite.default.locations.site_info.id` | 1.0.0.x (`parameters.yml.dist`: `65`), 1.1.0.x (`config/app/app.yaml`: `65`) | The site info object the templates read the site name, logo and social links from |
| `ngsite.default.locations.tree_root.id` | 2.5 and 1.0.0.x: `168`, the root of the shipped CJW data, in `parameters.yml.dist` (`master` said `2` before 2026-10-05) and, on the branch `1.0.0.x`, `default_parameters.yml` | The root of the public tree; a wrong value shows as a 404 on the home page ([chapter 8.3.4](08-configuration.md#834-100x-parameters-and-environment-variables)) |
| `ngsite.fh_group.locations.tree_root.id`, `ngsite.bold_group.locations.tree_root.id` | 1.1.0.x to 1.3.0.x, used as `content_tree_root` per siteaccess group | The roots of the two demo designs' trees |
| `ngsite.default.locations.ng_component_hero.id`, `...ng_component_quote.id` | 1.3.0.x (`config/app/prepends/netgen_layouts/components.yaml`) | The component containers Layouts reads |

Search the configuration for every location id before go-live; an id that exists in the demo tree but not in yours
gives a 404 or an empty block, not an error at deploy time:

```bash
grep -rnE "locations\.[a-z_]+\.id|location_id:" config app/config 2>/dev/null
```

The demo content types are the `ng_*` set (`ng_frontpage`, `ng_landing_page`, `ng_article`, `ng_news`,
`ng_blog_post`, `ng_gallery`, `ng_container` and others in the 1.0.0.x data). The site bundle's templates, view
configuration and Layouts block views are written for those types. Your own content types render with your own
templates; map them in the view configuration rather than renaming your types to `ng_*`. If you do want the demo
types in your repository, take them from the demo data with the migration bundle
(`kaliop:migration:generate --type=content_type --match-type=identifier --match-value=ng_article --mode=create`
on an installation that has them, then `kaliop:migration:migrate` on yours). The match type `identifier` is accepted
both by the 5.9 bundle of the 2.5 generation and by the bundle of 1.3.0.x; the longer names differ between them
(`contenttype_identifier` and `content_type_identifier`).

## 12.10 Moving page structure into Netgen Layouts

No tool converts Page Builder landing pages, ezflow pages or hand-built template structures into Netgen Layouts.
Plan it as a content task:

1. **List the page types** you have: per content type, which zones the page shows (header, main, sidebar, footer),
   which blocks each zone has, and where their content comes from (fixed items, a subtree, a relation list, tags).
2. **Build shared layouts first.** Header and footer are shared layouts whose zones the other layouts link to; build
   them once in the Layouts admin (`/nglayouts/admin` below the admin siteaccess, `/adminui/nglayouts/admin` on 1.1.0.x and
   later).
3. **Build one layout per page type**, with blocks backed by collections: manual collections for hand-picked items,
   query collections (content search, relation list query, tags query) for lists. The query types available are the
   ones the line's `layouts-*-relation-list-query` and `layouts-*-tags-query` packages register.
4. **Map layouts with rules**: a rule targets a location, a subtree, a content type or a path; a condition limits it
   to a siteaccess or a siteaccess group. Rules are evaluated per request, so a rule on a subtree covers content
   created later.
5. **Keep the old fields** until the layouts are live: a landing page field can stay on the content type and be ignored
   by the template, then be removed with a migration.

The Exponential book's [chapter 16.5.9](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1659-page-building-page-builder-against-netgen-layouts)
covers the vendor's commercial page tools and why they do not apply. The same layouts have been ported to the legacy
kernel as Exponential Layouts ([chapter 16.6.7](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md#1667-a-worked-port-netgen-layouts-and-the-media-site)),
which is the way back if you ever need it.

## 12.11 Moving layouts between installations

Netgen Layouts exports and imports layouts and rules as JSON:

```bash
php bin/console nglayouts:export layout <uuid>[,<uuid>...] > layouts.json
php bin/console nglayouts:export rule <uuid>[,<uuid>...] > rules.json
php bin/console nglayouts:export rule_group <uuid> > group.json        # where rule groups exist
php bin/console nglayouts:import layouts.json --mode=copy              # copy (default), overwrite or skip
```

The commands take UUIDs, which the Layouts admin shows in its URLs; to list them from the database:

```sql
SELECT uuid, name, shared FROM nglayouts_layout WHERE status = 1 ORDER BY name;    -- published layouts
SELECT uuid FROM nglayouts_rule WHERE status = 1;                                   -- published rules
```

A worked move of one shared layout and the layouts that use it, from a staging installation to production:

```bash
# on staging
php bin/console nglayouts:export layout 3c4f...,8a1b... --env=prod > layouts.json
# copy layouts.json to production, then there
php bin/console nglayouts:import layouts.json --mode=skip --env=prod     # keeps what already exists
php bin/console cache:clear --env=prod
```

Export a shared layout together with the layouts whose zones link to it, so the links find their target. `--mode=skip`
is the safe first run: entities that already exist are skipped and reported ("Skipped importing ..."), new ones are
imported; repeat with `--mode=overwrite` only for the entities you mean to replace.

The export refers to content by **remote id**, not by id: collection items and location-based rule targets are
written as remote ids and turned back into ids on import (`toLocationRemoteId()` and `toLocationId()` in the
integration's target types). A target whose remote id does not exist in the receiving repository is imported as `0`
and matches nothing. That is why content moved between installations should keep its remote ids, and why a layout
built on the demo site does not point at your content after an import unless your content carries the same remote ids.

## 12.12 Verify, roll back, checklist

Verify as in [chapter 11.10](11-upgrading-between-lines.md#1110-verify-the-upgrade): counts before and after, the
front page and one page per content type, a layout-managed page, the Layouts editor, an image variation, search,
forms, sign-in of an editor and of a user with an old password hash. Roll back by keeping the source system untouched
until the cut-over has settled; the migration is done on copies.

- [ ] Source chapter of the Exponential book read and its steps done.
- [ ] Target line chosen by generation; Nexus project created from its newest tag; installer not run on the data.
- [ ] Demo host maps replaced; admin siteaccess name decided.
- [ ] Layouts tables present; Layouts identifiers renamed where the line needs it.
- [ ] Site bundle location parameters point at your tree.
- [ ] Landing pages and page structures rebuilt as layouts and rules.
- [ ] Remote ids kept for content that layouts reference.
- [ ] Verification done on a fresh copy of production during the freeze.

## 12.13 References

In this repository:

- `composer.json` of each line (`git show <branch>:composer.json`), `app/config/parameters.yml.dist` and
  `app/config/ezplatform_siteaccess.yml` (1.0.0.x), `config/app/` (1.1.0.x and later)
- [doc/netgen/IBEXA_MIGRATIONS.md](../netgen/IBEXA_MIGRATIONS.md): the migration bundle
- [Chapter 11](11-upgrading-between-lines.md): the line upgrades and the Layouts identifier SQL

Exponential:

- [Chapter 14: Migrating from the 4.x line](https://github.com/se7enxweb/exponential/blob/main/doc/install/14-migrating-from-4x.md)
- [Chapter 15: Migrating from the 5.x legacy stack](https://github.com/se7enxweb/exponential/blob/main/doc/install/15-migrating-from-5x-legacy.md)
- [Chapter 16: Migrating from eZ Platform and Ibexa](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md)
- [Chapter 17: Migration reference](https://github.com/se7enxweb/exponential/blob/main/doc/install/17-migration-reference.md)

Vendor and Netgen:

- [Migrating from the 5.x platform stack](https://doc.ibexa.co/en/5.0/update_and_migration/migrate_to_ibexa_dxp/migrating_from_ez_publish_platform/)
- [Update from v2.5](https://doc.ibexa.co/en/latest/update_and_migration/from_2.5/update_from_2.5/)
- [Netgen Layouts documentation](https://docs.netgen.io/projects/layouts/en/latest/),
  [eZ Platform to Ibexa migration](https://docs.netgen.io/projects/layouts/en/1.4/getting_started/migrate_ezplatform_ibexa.html)
- [Netgen Media Site documentation](https://docs.netgen.io/projects/media-site/en/latest/)
