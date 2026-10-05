# 1. Introduction: what you are installing

This chapter explains what Exponential Platform Nexus is before you install it: a complete website project made of a
Symfony content platform, the Exponential legacy kernel on the lines that carry it, the Netgen Media Site design and
demo content, Netgen Layouts, Netgen Tags and the Netgen Site API. It sets out the release lines side by side (the
platform generation, Symfony, PHP and the legacy kernel of each) together with the fixes that are on the branches but
not yet in a release, explains how Nexus relates to Exponential 6 and to Exponential Platform Legacy, helps you choose
a line and avoid the two commands that install the wrong one, tours the directories of an installation, explains how
this book is organised and ends with a glossary. The later chapters assume the vocabulary introduced here.

[Contents](README.md) · Next: [2. Requirements](02-requirements.md)

## 1.1 What Exponential Platform Nexus is

**Exponential Platform Nexus** is a ready-made website project maintained by [7x](https://se7enx.com). You run
Composer, run one install command and get a working site: a content repository with versions, translations and a
content tree, a Twig front end with two demo designs, an administration interface, a page builder, a taxonomy, a REST
API and a GraphQL API. You then shape the content model, the design and the layouts into your own site, rather than
assembling dozens of bundles first.

The source code is on GitHub at
[github.com/se7enxweb/exponential-platform-nexus](https://github.com/se7enxweb/exponential-platform-nexus), and every
release is a Composer package of type `project` on
[Packagist](https://packagist.org/packages/se7enxweb/exponential-platform-nexus) under the name
`se7enxweb/exponential-platform-nexus`. The repository describes itself as "Platform v2/v3/v4/v5 + Exponential v6.x
(Legacy) + Netgen Media Site + NG Layouts + Symfony 7.4/5.4/3.4 PHP 8.0-8.5": Nexus is not one stack but one project
per platform generation, each on its own branch and series of tags (section [1.3](#13-the-release-lines)).

Nexus started as a fork of [Netgen Media Site](https://github.com/netgen/media-site), the blueprint project Netgen uses
to start client sites. 7x added the Exponential legacy kernel where the generation allows it, replaced upstream
packages that break on current PHP with `se7enxweb/*` forks, added installers that work on SQLite, PostgreSQL and
MySQL alike, and renamed the console commands to the `exponential:` prefix.

The licence of the 2.5 generation is the GNU General Public License, version 2 or later (`composer.json`:
`GPL-2.0-or-later`; `LICENSE` and `LICENSE.md` carry the GPL text). The `composer.json` of the 1.1.0.x line declares
`(GPL-2.0-or-later or proprietary)`, and that of the 1.2.0.x and 1.3.0.x lines `proprietary`, the value the upstream
skeleton carried. The licence files differ in the same way: 1.1.0.x and 1.2.0.x ship the GPL text as `LICENSE.md`
beside the upstream skeleton's `LICENSE` (which offers the upstream business licence or the GPL), while 1.3.0.x ships
only the upstream skeleton's `COPYRIGHT` and `LICENSE-bul` and no GPL text at all. The README of every line states
that Nexus is GNU GPL licensed. Until the metadata of the newer lines is corrected, read the README and the licence
files of the tag you install, and ask 7x if your legal review needs a statement in writing.

> **A note on names.** Nexus is built on products with older names, and the code keeps them on purpose. PHP
> namespaces stay upstream (`eZ\Publish\...` and `EzSystems\...` on the 2.5 and 3.3 generations, `Ibexa\...` on 4.6
> and 5), configuration keys are `ezpublish:` or `ibexa:`, database tables start with `ez` or `ibexa_`, and several
> commands still carry `ibexa:` or `ezplatform:` names. They are names of code, and this book writes them exactly as
> the code does. The products are called **Exponential Platform Nexus**, **Exponential Platform Legacy**,
> **Exponential** (the legacy kernel, version 6) and **Exponential Velocity** (the application server). The old
> product names (eZ Publish, eZ Platform, Ibexa) appear only where the book identifies the upstream generation a line
> is built on, for example "the 4.6 generation (upstream: Ibexa OSS 4.6)". Netgen is the real author of Layouts, the
> Media Site, Tags and the Site API and is named as such.

## 1.2 What is inside

Every line of Nexus combines the same kinds of parts. The versions differ by line; the table names the part and where
it comes from.

| Part | What it does | Where it comes from |
|---|---|---|
| **Platform kernel** (the content repository) | content types, content items with versions and translations, locations in a tree, users, roles and policies, search, the REST API | the se7enxweb forks of the upstream kernel: `se7enxweb/ezpublish-kernel` (2.5), `se7enxweb/ezplatform-kernel` through the metapackage `se7enxweb/oss ~3.3.0` (3.3), `se7enxweb/oss ~4.6.0` (4.6), `se7enxweb/exponential-platform-dxp` with `se7enxweb/exponential-platform-dxp-core` (5) |
| **Administration interface** | the editors' and administrators' interface: content tree, editing, users, roles, system information | `se7enxweb/ezplatform-admin-ui` (2.5), the admin UI of the metapackage (3.3, 4.6), `se7enxweb/admin-ui` (5); on 2.5 to 4.6 also the Netgen admin UI (`ngadminui`) from `se7enxweb/admin-ui-bundle` |
| **Exponential legacy kernel** | the Exponential 6 kernel with its own administration (`legacy_admin`), templates and modules, sharing the database with the Symfony stack | `se7enxweb/exponential`, installed into `ezpublish_legacy/` and connected through a LegacyBridge (`se7enxweb/legacy-bridge`, on 4.6 `se7enxweb/ibexa-legacy-bridge`); on the 2.5, 3.3 and 4.6 generations only |
| **Netgen Media Site** | the site bundle (controllers, menus, view configuration, the `ngsite:*` console commands), the designs and the demo content | `se7enxweb/site-bundle` (a fork that replaces `netgen/site-bundle`), the project's own `templates/` and `assets/`, and the demo data package |
| **Netgen Layouts** | the page builder: layouts, zones, blocks, collections and the rules that map layouts to pages | `netgen/layouts-core` (on 5 the fork `se7enxweb/layouts-core`), `netgen/layouts-ui`, `netgen/layouts-standard` and the platform integration (`layouts-ezplatform` or `layouts-ibexa`) |
| **Netgen Content Browser** | the item picker Layouts uses to select content | `netgen/content-browser` and its platform integration |
| **Netgen Tags** | a hierarchical taxonomy with its own field type and administration | `se7enxweb/tagsbundle` (2.5) or `netgen/tagsbundle` |
| **Netgen Site API** | a read-oriented layer over the repository for front-end code and templates: content and locations with their fields already in the current language | `netgen/ezplatform-site-api` (2.5), `se7enxweb/ezplatform-site-api` (3.3), `netgen/ibexa-site-api` (4.6, 5) |
| **Other Netgen bundles** | information collection (contact forms), Open Graph, metadata, search extras, enhanced link, scheduled visibility, toolbar and more | `netgen/*` packages listed in each line's `composer.json` |
| **Installers** | the console commands that create the schema and load the demo content | `ezplatform:install` on 2.5, `exponential:install` on 3.3, 4.6 and 5 ([chapter 4](04-installing.md)) |

What Nexus does **not** contain is equally important. It is built on the open source edition of the upstream
platform: there is no Page Builder, Form Builder, editorial workflow, personalisation or commerce. Netgen Layouts takes
the place of the page builder.

## 1.3 The release lines

Each Nexus line is one platform generation. The facts below are read from the `composer.json` of each branch and
release tag; [chapter 3](03-getting-the-code.md#31-branches-tags-and-packagist-versions) lists every tag.

| Line | Branch | Release tags | Platform generation (upstream) | Symfony | PHP (`composer.json`) | Exponential legacy kernel |
|---|---|---|---|---|---|---|
| **2.5** | `master` (default branch) | `v2.5.0.0` to `v2.5.0.6`, also `1.0.0.8` and `1.0.0.10` | 2.5 (eZ Platform 2.5 LTS): `se7enxweb/ezpublish-kernel ~7.5.40` | 3.4: `se7enxweb/symfony v3.4.55` | `^7.1.3 \|\| ^7.2 \|\| ^7.4 \|\| ^8.0 \|\| ... \|\| ^8.6` (`v2.5.0.0` and `v2.5.0.1`: `^7.1.3 \|\| ^8.1 \|\| ^8.2`); in practice 8.1 or newer, see below | yes: `se7enxweb/exponential ^6.0.12`, `se7enxweb/legacy-bridge ^2.1` |
| **1.0.0.x** | `1.0.0.x` | `v1.0.0.0.1` to `v1.0.0.0.3`, `1.0.0.4` to `1.0.0.7`, `1.0.0.9` | as 2.5 | 3.4 | as 2.5 | yes, as 2.5 |
| **1.1.0.x** | `1.1.0.x` | `v1.1.0.0` to `v1.1.0.7` | 3.3 (eZ Platform 3.3): `se7enxweb/oss ~3.3.0`, `se7enxweb/ezplatform-kernel ~1.3` | 5.4 (`symfony/framework-bundle 5.4.*`) | `^8.0` | yes: `se7enxweb/legacy-bridge ^3.0`, `se7enxweb/site-legacy-bundle ^2.0` |
| **1.2.0.x** | `1.2.0.x` | `v1.2.0.0` | 4.6 (Ibexa OSS 4.6): `se7enxweb/oss ~4.6.0` | 5.4 | `>=8.2` | yes: `se7enxweb/site-legacy-bundle v2.0.0` pulls `se7enxweb/ibexa-legacy-bridge 4.x` and `se7enxweb/exponential` |
| **1.3.0.x** | `1.3.0.x` | `1.3.0.0.0`, `1.3.0.1` to `1.3.0.5` (the newest release) | 5 (Ibexa OSS 5.0): `se7enxweb/exponential-platform-dxp dev-master` | 7.4 (`symfony/framework-bundle 7.4.*`) | `>=8.4` | no |

How to read the table:

- **The 2.5 generation has two branches.** `master` and `1.0.0.x` share their history up to early 2026 and then
  diverged. `master` carries the `v2.5.0.x` tags (the 2.5 tag series, "Platform Legacy 2.5 packaged as Nexus") and
  installs the CJW demo content from the package `se7enxweb/cjw-exponential-media-site-data` (since `v2.5.0.4`).
  `1.0.0.x` installs the Netgen demo content from `netgen/media-site-data ~1.8.1`, ships SQL dumps of the CJW content
  in the repository and adds an SQLite installer type (since `1.0.0.6`). Both run the same Symfony 3.4 stack, the same
  kernel and the same legacy kernel. The four-part `1.0.0.y` tags do not all sit on one branch: `1.0.0.8` and
  `1.0.0.10` were cut from `master`, `1.0.0.9` from `1.0.0.x`. A Composer constraint such as `~1.0.0.7` therefore ends
  on `1.0.0.10`, which is `master` code with the CJW demo package, not the `1.0.0.x` branch
  ([chapter 3](03-getting-the-code.md#31-branches-tags-and-packagist-versions)).
- **The PHP column is what Composer checks, not what was tested.** The 2.5 generation declares every PHP version up
  to 8.6, but the legacy kernel it installs (`se7enxweb/exponential`) requires PHP 8.1, so 8.1 is the real minimum.
  The install guide of the line still tells you to run Composer with `--ignore-platform-reqs`, because some
  dependencies of the Symfony 3.4 stack declare older limits. The README of `master` now says "PHP 8.1 or newer"
  (the README released with `v2.5.0.6` said "PHP 8.3 -> 8.5"). The 7x guides of the newer lines recommend PHP 8.5
  and say the lines were tested on PHP 8.5.5. [Chapter 2](02-requirements.md#22-php) has the detail.
- **The legacy kernel goes away with the 5 generation.** The 2.5, 3.3 and 4.6 lines install the Exponential 6 kernel
  into `ezpublish_legacy/` and offer its administration as the `legacy_admin` siteaccess; 1.3.0.x is a Symfony-only
  project.
- **The newer lines resolve development versions.** Every line sets `"minimum-stability": "dev"` with
  `"prefer-stable": true`, and several requirements are branches (`se7enxweb/exponential-platform-dxp dev-master` and
  `se7enxweb/site-bundle 5.0.x-dev` on 1.3.0.x, `se7enxweb/oss 4.6.x-dev` in the lock file of 1.2.0.x). Two
  installations of the same tag made on different days can therefore differ. Keep the `composer.lock` your
  installation produced ([chapter 3](03-getting-the-code.md#36-the-lock-file-is-part-of-your-site)).

### What the release notes say about each line

| Release | Date | What it brought (from the GitHub release notes) |
|---|---|---|
| `1.3.0.5` | 2026-08-03 | production defaults in the root `.htaccess` (`APP_ENV=prod`, `APP_DEBUG 0`), the configuration resolver exposed as a public service for `prod`, `config/reference.php` |
| `1.3.0.4` | 2026-08-03 | a default configuration fix for the JWT token settings |
| `v2.5.0.6` | 2026-07-04 | `netgen/media-site-data` (about 180 MB) moved from `require` to `suggest` |
| `v2.5.0.4` | 2026-07-04 | the installer type `cjw-exponential-media`, its data moved to `se7enxweb/cjw-exponential-media-site-data` |
| `1.0.0.10` (from `master`) | 2026-07-04 | a rewritten `doc/INSTALL.md` and rebuilt front-end assets |
| `1.0.0.9` (from `1.0.0.x`) | 2026-07-04 | UTF-8 corruption in the MySQL installer SQL fixed (Layouts block translations) |
| `1.0.0.8` (from `master`) | 2026-07-04 | the same SQL fix, and `NODE_OPTIONS=--openssl-legacy-provider` removed from `package.json` (it came back later on `master`) |
| `1.0.0.6` | 2026-04-22 | the installer type `exponential-cjw` with full SQLite seed data |
| `v1.2.0.0` | 2026-04-20 | the first release of the 4.6 line: `exponential:install`, SQLite support, `ngadminui` |
| `v1.1.0.7` | 2026-04-20 | `exponential:install exponential-media` with SQLite support, the SQLite gateway override |

The release notes of `v1.2.0.0` call the line "Symfony 6.4 LTS, PHP 8.1+"; its `composer.json` requires
`symfony/framework-bundle 5.4.*` and PHP `>=8.2`, which is what Composer enforces and what this book uses.

### Fixed on the branches, not yet released

On 2026-10-05 every branch received fixes that no release tag contains yet. They will reach Composer users with the
next release of each line: `v2.5.0.7` on `master` and the next tag of `1.0.0.x`, `1.1.0.x`, `1.2.0.x` and
`1.3.0.x`. Until those tags exist, they are **upcoming**; a site installed from `v2.5.0.6`, `1.0.0.9`, `v1.1.0.7`,
`v1.2.0.0` or `1.3.0.5` does not have them, and a clone of the branch does. The ones that change what you do:

| Fix | Lines | Where the book covers it |
|---|---|---|
| `web/app.php` no longer switches to the `dev` environment when the `Host` header contains `dev.` | 2.5, 1.0.0.x | [6.2](06-serving-the-site.md#62-how-a-request-reaches-the-application-per-line) |
| `app/AppCache.php` no longer turns private (per-user) responses into public, cacheable ones | 2.5, 1.0.0.x | [6.7](06-serving-the-site.md#67-the-http-cache-symfony-proxy-varnish-and-foshttpcache) |
| `TRUSTED_PROXIES` is read (`framework.trusted_proxies`); before, nothing read it | 1.1.0.x, 1.2.0.x, 1.3.0.x | [6.10](06-serving-the-site.md#610-https-reverse-proxies-and-trusted-proxies) |
| `APP_HTTP_CACHE=1` puts the Symfony HTTP cache proxy in front of the kernel; before, nothing read it | 1.1.0.x, 1.2.0.x, 1.3.0.x | [6.7](06-serving-the-site.md#67-the-http-cache-symfony-proxy-varnish-and-foshttpcache) |
| `config/app/server/prod.yaml` exists, so `SERVER_ENVIRONMENT=prod` builds | 1.2.0.x, 1.3.0.x (1.1.0.x had it; its `.env` now defines the variables it reads) | [4.10](04-installing.md#410-what-can-go-wrong-across-lines) |
| `.nvmrc` on the 2.5 generation (`v22` on `master`, `v20` on `1.0.0.x`), and the `Makefile` passes its version to `nvm install` | all | [2.5](02-requirements.md#25-nodejs-and-yarn) |
| `make clear-all-cache` empties the configured cache pools; `make ibexa-assets` builds the admin assets | all | [chapter 10](10-operations.md) |
| `make reindex` calls `exponential:reindex`; `public/index_cluster.php` works without a legacy root | 1.3.0.x | [6.2](06-serving-the-site.md#62-how-a-request-reaches-the-application-per-line) |
| The front-end root location defaults to 168, the root of the CJW content | 1.0.0.x | [4.4](04-installing.md#44-the-100x-branch) |
| `.gitignore` ignores `.env.local`, `.env.*.local` and `.env.php` | 2.5, 1.0.0.x, 1.1.0.x | [chapter 14](14-security-hardening.md) |

One fix lives in a dependency rather than in this repository: `se7enxweb/exponential-platform-dxp-core` `v5.0.9`
(released 2026-10-05) makes the 1.3.0.x installer find the Netgen Layouts schema of the `se7enxweb/layouts-core`
fork. The lock file of `1.3.0.5` and of the branch still pins `v5.0.7`;
[chapter 4](04-installing.md#47-the-130x-line) says what to do about it.

## 1.4 Nexus, Exponential 6 and Exponential Platform Legacy

Three products share the Exponential name and, in part, the same code. They differ in which kernel runs the site.

| | **Exponential 6** | **Exponential Platform Legacy** | **Exponential Platform Nexus** |
|---|---|---|---|
| Repository | [se7enxweb/exponential](https://github.com/se7enxweb/exponential) | [se7enxweb/exponential-platform-legacy](https://github.com/se7enxweb/exponential-platform-legacy) | [se7enxweb/exponential-platform-nexus](https://github.com/se7enxweb/exponential-platform-nexus) |
| What runs the site | the legacy kernel alone: INI settings, TPL templates, legacy modules | the legacy kernel with a Symfony stack beside it, through LegacyBridge | the Symfony platform: YAML configuration, Twig templates, Symfony controllers; the legacy kernel beside it on 2.5 to 4.6 |
| Page builder | Exponential Layouts (the `explayouts*` extensions) | as Exponential 6 | Netgen Layouts |
| Demo site | the site packages of the setup wizard | as the Symfony skeleton of its line | Netgen Media Site (Fit & Healthy, Bold Agency) or the CJW demo on the 2.5 line |
| Lines | 6.0.x | `v2.5.0.x`, `v3.3.44.x`, `v4.6.23.x`, `v5.0.x` | as in section [1.3](#13-the-release-lines) |
| Its own application server | Exponential Velocity | as Exponential 6 for the legacy part | see [chapter 6](06-serving-the-site.md) |

The legacy kernel that Nexus 2.5 to 4.6 installs **is** Exponential 6: the same Composer package,
`se7enxweb/exponential`, placed in `ezpublish_legacy/` by the Composer plugin
`se7enxweb/exponential-legacy-installer`. The Symfony stack and the legacy kernel read and write one database, so an
editor can work in either administration and see the same content. What the legacy part of a Nexus installation can
do, and how it is configured, is documented in the
[Exponential 6 book](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md); this book covers it only
where Nexus does something different.

Moving an existing site into Nexus, from the upstream products or from Exponential, is the subject of
[chapter 12](12-migrating-into.md). The Exponential 6 book's chapter
[Migrating from eZ Platform and Ibexa](https://github.com/se7enxweb/exponential/blob/main/doc/install/16-migrating-from-ez-platform-and-ibexa.md)
compares the two directions from the other side.

## 1.5 Choosing a line

| You want | Choose |
|---|---|
| A new site on current software, no legacy kernel needed | **1.3.0.x**: Symfony 7.4, PHP 8.4 or newer, Node.js 22 |
| A new site that also needs the Exponential legacy administration or legacy extensions | **1.2.0.x** (Symfony 5.4, PHP 8.2 or newer) or **1.1.0.x** (Symfony 5.4, PHP 8.0 or newer) |
| To move an existing site of the 2.5 generation without changing generation | the **2.5** line (`master`, `v2.5.0.x`) or **1.0.0.x** |
| The CJW demo content in German and English | the **2.5** line (`cjw-exponential-media`), or `1.0.0.x` (SQL dumps, `exponential-cjw` on SQLite) |
| The Netgen Media Site demo (Fit & Healthy, Bold Agency) | **1.1.0.x**, **1.2.0.x** or **1.3.0.x** (`exponential-media`) |
| PHP 8.0 servers | 1.1.0.x only (the 2.5 generation's legacy kernel needs 8.1) |
| PHP 8.1 servers | 1.1.0.x, or the 2.5 generation |
| PHP 8.4 or 8.5 | 1.3.0.x; the older lines are declared for these versions too (see [chapter 2](02-requirements.md)) |
| A site without a separate database server | 1.1.0.x, 1.2.0.x and 1.3.0.x install onto SQLite; on the 2.5 generation only `1.0.0.x` has an SQLite installer |

Rules of thumb:

- **Same generation first.** If you move an existing site, pick the line of its generation and change generation
  later ([chapter 11](11-upgrading-between-lines.md)).
- **Start new projects on 1.3.0.x** unless you need the legacy kernel.
- **Install the newest release of the line, then keep its lock file.** Several dependencies are branches.

### Two ways to get the wrong line by accident

Both have caught real installations. Name the line every time.

1. **`composer create-project` without a version installs upstream Media Site 3.1.6.** Packagist lists the upstream
   tags of `netgen/media-site` under the Nexus package name, and `3.1.6` is the highest stable version number there.
   So

   ```bash
   composer create-project se7enxweb/exponential-platform-nexus nexus
   ```

   installs Netgen's Media Site 3.1.6 (built on upstream `ibexa/oss ~4.5.0`, `netgen/*` packages, no 7x forks, no
   legacy kernel, no `exponential:install`), not any Nexus line. You notice it when `composer.json` in the new directory says
   `"name": "netgen/media-site"`. Always give a version: `se7enxweb/exponential-platform-nexus:1.3.0.5`.
2. **`git clone` without a branch gives `master`, which is the 2.5 generation.** The default branch is the oldest
   platform generation, with `app/`, `web/` and Symfony 3.4. If you wanted 1.3.0.x, clone with `-b 1.3.0.x` or run
   `git checkout 1.3.0.x` before `composer install`. `ls web/app.php` succeeding is the sign that you are on the 2.5
   generation.

[Chapter 3](03-getting-the-code.md) gives the exact commands per line.

## 1.6 The parts of an installation

An installation is one directory, the **project root**: the directory that holds `composer.json`, `bin/console` and
`vendor/`. The web server's document root is a directory below it: `web/` on the 2.5 generation, `public/` on the
others. Never point a web server at the project root itself.

The 2.5 generation (branches `master` and `1.0.0.x`) uses the Symfony 3.4 layout:

```
project root (2.5 generation)
├── app/                    AppKernel.php, app/config/ (config.yml, parameters.yml, ezplatform_siteaccess.yml)
├── bin/console             the Symfony console
├── src/AppBundle/          the project bundle: templates, Sass, ES6, translations, the legacy extension "app"
├── web/                    document root: app.php (front controller), assets/, bundles/, var/
├── ezpublish_legacy/       the Exponential legacy kernel, placed here by Composer (replaced on every update)
├── var/                    cache/, logs/, sessions; the SQLite database var/data_<env>.db on 1.0.0.x
├── vendor/                 the packages Composer installs
├── doc/                    the documentation, this book among it
├── package.json            the front-end build (Webpack Encore 0.27)
└── composer.json
```

The 1.1.0.x, 1.2.0.x and 1.3.0.x lines use the Symfony Flex layout:

```
project root (1.1.0.x to 1.3.0.x)
├── .env                    defaults for environment variables; your values go in .env.local
├── assets/                 the site's JavaScript and Sass sources
├── bin/console             the Symfony console
├── config/                 bundles.php, packages/, routes/, app/ (the Media Site configuration)
│   └── app/server/         per-server parameters, chosen by SERVER_ENVIRONMENT (location IDs, domains)
├── data/                   the demo SQL for exponential:install (1.1.0.x and 1.2.0.x; on 1.3.0.x it ships in vendor/)
├── src/                    the project's PHP code: Kernel.php, controllers, the installer classes the project adds
├── templates/              Twig templates: themes/app, themes/fh, themes/bold, themes/common, nglayouts/
├── public/                 document root: index.php, assets/, bundles/, var/ (uploaded files)
├── ezpublish_legacy/       the legacy kernel (1.1.0.x and 1.2.0.x only)
├── translations/           translation files of the site
├── var/                    cache/, log/, sessions/, the SQLite database var/data_<env>.db
├── vendor/                 the packages Composer installs
├── package.json            the front-end build (Webpack Encore)
└── composer.json
```

[Chapter 3](03-getting-the-code.md#37-a-tour-of-the-project-root) tours the directories in detail.

### Siteaccesses

As in every product of this family, a **siteaccess** is one way of reaching the installation, with its own settings,
design and languages. Every request is matched to exactly one siteaccess. The lines ship these:

| Line | Siteaccesses | Default | How they are matched |
|---|---|---|---|
| 2.5 (`master`, `1.0.0.x`) | `de`, `en` (public, design `cjw_app`), `admin` (platform admin), `ngadminui` (Netgen admin UI), `legacy_admin` (legacy kernel) | `de` | `Map\URI` for `de`, `en` (and `ngadminui` on `master`), `Map\Host` for all, with the host names of the CJW demo servers, which you replace; `admin` and `legacy_admin` are reachable only by host name |
| 1.1.0.x, 1.2.0.x | `fh_eng` (Fit & Healthy), `bold_eng`, `bold_ger` (Bold Agency), `adminui`, `ngadminui`, `legacy_admin` | `fh_eng` | `URIElement: 1` (the first path element) |
| 1.3.0.x | `fh_eng`, `bold_eng`, `bold_ger`, `adminui` | `fh_eng` | `URIElement: 1`; a `Map\Host` example is commented out |

The name of the administration siteaccess on 1.1.0.x to 1.3.0.x comes from the parameter
`ngsite.admin_siteaccess_name: adminui`. [Chapter 5](05-the-demo-site-and-layouts.md#53-siteaccesses-designs-and-languages)
explains the demo's siteaccesses, and [chapter 8](08-configuration.md) how to change them.

## 1.7 How this book is organised

| Part | Chapters |
|---|---|
| Before you install | [1. Introduction](01-introduction.md), [2. Requirements](02-requirements.md), [3. Getting the code](03-getting-the-code.md) |
| Installing | [4. Installing](04-installing.md), [5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md) |
| Running the site | [6. Serving the site](06-serving-the-site.md), [7. Databases](07-databases.md), [8. Configuration](08-configuration.md), [9. Front end and themes](09-frontend-and-themes.md), [10. Operations](10-operations.md) |
| Keeping it running | [11. Upgrading between lines](11-upgrading-between-lines.md), [12. Migrating into Nexus](12-migrating-into.md), [13. Troubleshooting](13-troubleshooting.md), [14. Security hardening](14-security-hardening.md) |

The short version of the install is [doc/INSTALL.md](../INSTALL.md).

### Conventions

- **Where commands run.** Every command runs in the project root unless the text says otherwise. `php bin/console`
  is the Symfony console of the installation.
- **Per-line instructions.** When a step differs by line, the chapter gives a table or one subsection per line, named
  by the line: **2.5** (branch `master`, tags `v2.5.0.x`), **1.0.0.x**, **1.1.0.x**, **1.2.0.x**, **1.3.0.x**.
  "The 2.5 generation" means both 2.5 and 1.0.0.x.
- **Placeholders** are written in angle brackets: `<db_name>`, `<web-user>`. Replace them, brackets included.
- **Settings.** Environment variables are written as they appear in `.env` (`DATABASE_URL=...`); YAML keys with their
  full path (`ibexa.siteaccess.list`).
- **Verified facts.** Package names, constraints, commands and file names in this book were read from the branches
  and tags of the repository, from `composer.lock` where one is committed, from the package metadata Packagist
  serves, and from a running 1.3.0.x installation. Chapters 1 to 7 were checked against the branches as they stood on
  2026-10-05, after that day's fixes; where a release tag behaves differently from its branch, the text says which.
  Where the repository's own guides disagree with the code, the book follows the code and says so. Steps that could
  not be checked against the code are marked as such.

## 1.8 Glossary

| Term | Meaning |
|---|---|
| project root | The directory with `composer.json`, `bin/console` and `vendor/`. |
| document root | The directory the web server serves: `web/` (2.5 generation) or `public/` (newer lines). |
| line | One generation of Nexus with its own branch and tag series: 2.5, 1.0.0.x, 1.1.0.x, 1.2.0.x, 1.3.0.x. |
| platform generation | The upstream release family a line is built on: 2.5, 3.3, 4.6 or 5. |
| fork | A `se7enxweb/*` package that replaces an upstream package. It declares the upstream name under `replace`, so packages requiring the upstream name are satisfied by the fork. |
| metapackage | A Composer package that only requires others: `se7enxweb/oss` (3.3, 4.6), `se7enxweb/exponential-platform-dxp` (5). |
| legacy kernel | Exponential 6 (`se7enxweb/exponential`) installed in `ezpublish_legacy/`. |
| LegacyBridge | The bundle that runs the legacy kernel inside the Symfony application and shares the database and session with it. |
| siteaccess | One way of reaching the installation, with its own settings, design and languages, matched by URL, host or port. |
| siteaccess group | A named set of siteaccesses that share settings (`frontend_group`, `admin_group`). |
| design, theme | A named template search path of the design engine (`fh`, `bold`, `app`, `common`, `standard`); a design is a list of themes. |
| content type | The definition of a kind of content (`ng_article`, `ng_recipe`) as a list of fields. Called a content class in the legacy kernel. |
| content item, location | A piece of content, and its place in the content tree (one item can have several locations). |
| installer type | The argument of the install command that selects which schema and data to load (`exponential-media`, `cjw-exponential-media`, `netgen-media`). |
| demo data | The content, layouts, tags and images an installer type loads. |
| layout | In Netgen Layouts, the arrangement of a page: a layout type with zones, filled with blocks. |
| zone | A named area of a layout (`header`, `main`, `footer`) that holds blocks. |
| block | One unit on a page (title, list, gallery, component), made from a block type and configured by parameters. |
| collection | The items a block shows: picked by hand (manual) or fetched by a query (dynamic). |
| layout mapping, rule | A rule that says which layout a page uses: a target (a location, a subtree, a URL prefix) plus optional conditions. |
| shared layout | A layout that is never mapped to a page; its zones are linked into other layouts (header and footer, pre-footer). |
| Tags | Netgen Tags: a tree of keywords with a field type (`eztags`) and its own administration. |
| Site API | Netgen Site API: a read layer over the repository used by templates and front-end controllers. |
| `ngadminui` | The Netgen administration interface siteaccess (2.5 to 4.6). |
| `.env.local` | The file in the project root with your values for the environment variables of `.env`; never committed. |
| `parameters.yml` | The file in `app/config/` with your values on the 2.5 generation; created by Composer from `parameters.yml.dist`. |
| `SERVER_ENVIRONMENT` | The Media Site variable that selects the per-server configuration in `config/app/server/` (1.1.0.x to 1.3.0.x). |
| Webpack Encore | The front-end build tool that compiles `assets/` (or `src/AppBundle/Resources/`) into the document root. |
| Flex recipe | Configuration that Symfony Flex adds or removes when a package is installed or removed. |
| Exponential Velocity | The application server of the Exponential family (`se7enxweb/exponential-velocity`); see [chapter 6](06-serving-the-site.md). |

## References

In this repository:

- [The short installation guide](../INSTALL.md).
- The project README of each line (`README.md` on the branch you install).
- The 7x installation and operations guides: `doc/sevenx/INSTALL.md` on 1.1.0.x, 1.2.0.x and 1.3.0.x;
  `doc/INSTALL.md` on `1.0.0.x` and in the `v2.5.0.6` release (`git show v2.5.0.6:doc/INSTALL.md`). On `master`,
  [doc/INSTALL.md](../INSTALL.md) is now the short guide into this book.
- The upstream Netgen notes: [doc/netgen/INSTALL.md](../netgen/INSTALL.md), [doc/netgen/FRONTEND.md](../netgen/FRONTEND.md),
  [doc/netgen/LAUNCHPAD.md](../netgen/LAUNCHPAD.md) (these describe `netgen/media-site`, not Nexus).
- The CJW notes in [doc/cjw/README.md](../cjw/README.md).

External:

- Source code: [github.com/se7enxweb/exponential-platform-nexus](https://github.com/se7enxweb/exponential-platform-nexus);
  the starter of the 5 generation: [github.com/se7enxweb/exponential-platform-nexus-starter](https://github.com/se7enxweb/exponential-platform-nexus-starter).
- Composer package: [packagist.org/packages/se7enxweb/exponential-platform-nexus](https://packagist.org/packages/se7enxweb/exponential-platform-nexus);
  the version list Composer itself reads: [repo.packagist.org/p2/se7enxweb/exponential-platform-nexus.json](https://repo.packagist.org/p2/se7enxweb/exponential-platform-nexus.json).
- Release notes: [github.com/se7enxweb/exponential-platform-nexus/releases](https://github.com/se7enxweb/exponential-platform-nexus/releases).
- Exponential 6: [the Exponential 6 book](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md);
  Exponential Platform Legacy: [github.com/se7enxweb/exponential-platform-legacy](https://github.com/se7enxweb/exponential-platform-legacy).
- Netgen: [Netgen Layouts documentation](https://docs.netgen.io/projects/layouts/en/latest/),
  [Netgen Site API documentation](https://docs.netgen.io/projects/site-api/en/latest/),
  [Netgen Media Site documentation](https://docs.netgen.io/projects/media-site/en/latest/),
  [Netgen Tags](https://github.com/netgen/TagsBundle), [Netgen Media Site source](https://github.com/netgen/media-site).
- Upstream concepts: [content model](https://doc.ibexa.co/en/5.0/content_management/content_model/) and
  [installation](https://doc.ibexa.co/en/5.0/getting_started/install_ibexa_dxp/) in the upstream documentation.
- Symfony: [release schedule](https://symfony.com/releases), [configuration and environment variables](https://symfony.com/doc/current/configuration.html).
- PHP: [supported versions](https://www.php.net/supported-versions.php).
- 7x: [se7enx.com](https://se7enx.com).

[Contents](README.md) · Next: [2. Requirements](02-requirements.md)
