# 3. Getting the code

This chapter explains how to put the code of an Exponential Platform Nexus line on disk: which branches and tags
exist and which of them Packagist offers, `composer create-project` for each line with the exact package name and
version, cloning the repository with git, the key packages each line pulls in, what Composer runs after it installs,
why the lock file belongs to your site, and a tour of the project root. It ends with the sister repositories you may
meet. Installing the database and the demo content follows in [chapter 4](04-installing.md).

[Contents](README.md) · Previous: [2. Requirements](02-requirements.md) · Next: [4. Installing](04-installing.md)

## 3.1 Branches, tags and Packagist versions

The repository is [se7enxweb/exponential-platform-nexus](https://github.com/se7enxweb/exponential-platform-nexus). Its
default branch is `master`. The release lines and their tags:

| Line | Branch | Tags in git | Versions Packagist offers | Newest release |
|---|---|---|---|---|
| 2.5 | `master` | `v2.5.0.0` to `v2.5.0.6`, `1.0.0.8`, `1.0.0.10` | the same, and `dev-master` | `v2.5.0.6` (2026-07-04); `v2.5.0.7` upcoming |
| 1.0.0.x | `1.0.0.x` | `v1.0.0.0.1` to `v1.0.0.0.3`, `1.0.0.4` to `1.0.0.7`, `1.0.0.9` | `1.0.0.4` to `1.0.0.7`, `1.0.0.9`, `1.0.0.x-dev` | `1.0.0.9` (2026-07-04) |
| 1.1.0.x | `1.1.0.x` | `v1.1.0.0` to `v1.1.0.7` | the same, `1.1.0.x-dev` | `v1.1.0.7` (2026-04-20) |
| 1.2.0.x | `1.2.0.x` | `v1.2.0.0` | the same, `1.2.0.x-dev` | `v1.2.0.0` (2026-04-20) |
| 1.3.0.x | `1.3.0.x` | `1.3.0.0.0`, `1.3.0.1` to `1.3.0.5` | `1.3.0.1` to `1.3.0.5`, `1.3.0.x-dev` | `1.3.0.5` (2026-08-03) |

The "Versions Packagist offers" column was read from the metadata Composer downloads
(`https://repo.packagist.org/p2/se7enxweb/exponential-platform-nexus.json` and its `~dev` companion) on 2026-10-05.
Packagist also still lists old development branches (`2.5.0.0-dev`, `2.5.0.1-dev`, `1.0.0.3-dev` and the upstream
`1.7.x-dev` to `2.2.x-dev`); ignore them.

Things to know before you pick a version:

- **A version without a number installs upstream, not Nexus.** Nexus is a fork of `netgen/media-site`, and the
  repository keeps Netgen's tags (`1.0.0` to `1.12.2`, `2.0.0` to `2.3.2`, `3.0.0` to `3.1.6`). Packagist lists them
  under the Nexus name. Composer picks the highest stable version when none is given, and that is `3.1.6`, the
  upstream Media Site on `ibexa/oss ~4.5.0`. So `composer create-project se7enxweb/exponential-platform-nexus`
  without `:<version>` installs **upstream Media Site 3.1.6**, and `composer require` with `*` or `^3.0` does the same.
  A Nexus version always has four numbers (`1.3.0.5`, `v2.5.0.6`); always name one.
- **The `1.0.0.y` tags are split between two branches.** `1.0.0.8` and `1.0.0.10` were cut from `master` (the CJW demo
  package `se7enxweb/cjw-exponential-media-site-data`), `1.0.0.4` to `1.0.0.7` and `1.0.0.9` from `1.0.0.x` (the
  Netgen demo `netgen/media-site-data`, the SQL dumps, the SQLite installer). Composer sorts them as one series, so a
  floating constraint crosses branches: `~1.0.0.4` (`>=1.0.0.4 <1.0.1.0`) resolves to `1.0.0.10`, that is `master`
  code. To stay on the `1.0.0.x` branch, pin an exact version (`1.0.0.9`).
- **Use `~`, never `^`, with a Nexus version.** The caret allows everything below the next major version, and the
  upstream tags live there: `^1.0.0.4` and `^1.3.0.5` both mean `<2.0.0`, which the upstream `1.12.2` satisfies and
  outranks, so Composer installs upstream Media Site 1.12.2. The tilde on a four-part version only floats the last
  number: `~1.3.0.5` is `>=1.3.0.5 <1.3.1.0`.
- **Five-part tags are not on Packagist.** Composer versions have at most four numbers, so `v1.0.0.0.1` to
  `v1.0.0.0.3` and `1.3.0.0.0` exist only in git. The 1.0.0.x guide's command
  `composer create-project se7enxweb/exponential-platform-nexus:v1.0.0.0.3` therefore cannot resolve; use `1.0.0.9`
  or a git checkout of the tag.
- **The branches move ahead of the tags.** All five branches have commits after their newest tag: on 2026-10-05 each
  received the fixes listed in [chapter 1](01-introduction.md#fixed-on-the-branches-not-yet-released) (the
  environment switch and the cache handling of `web/app.php` and `AppCache` on the 2.5 generation, trusted proxies and
  the HTTP cache proxy on 1.1.0.x to 1.3.0.x, `.nvmrc` and Makefile fixes everywhere). A branch is what you clone to
  work on Nexus itself or to get a fix before its release; a tag is what you install a site from.
- **`dev-master` and `1.0.0.x-dev`.** `master`'s `composer.json` declares the branch alias `dev-master` →
  `1.0.0.x-dev`, which is also the version name of the real `1.0.0.x` branch. Packagist resolves `1.0.0.x-dev` to the
  `1.0.0.x` branch (commit `4246d1244` on 2026-10-05). Ask for `dev-master` when you mean `master`, and after any
  install from a branch check which commit you got: `composer show se7enxweb/exponential-platform-nexus` in a project
  that requires it, or `git rev-parse HEAD` in a clone.
- **Check what is current.** The tables of this book were taken from the repository on the day it was written. List
  the versions yourself:

```bash
composer show --all se7enxweb/exponential-platform-nexus | grep -E '^versions'
git ls-remote --tags https://github.com/se7enxweb/exponential-platform-nexus.git | sort -V -k2 | tail
```

A plain lexical sort orders `1.0.0.10` before `1.0.0.8`; use `sort -V` or `--sort=version:refname`. The `git`
command prints one line per tag with the commit it points to. Because `sort -V` puts the `v`-prefixed tags after the
plain ones, its last lines are the 2.5 series, not the newest tag of your line; filter for your line
(`| grep 'tags/1.3.0'`):

```text
450750c735a8...	refs/tags/v2.5.0.5
461810fd2b3a...	refs/tags/v2.5.0.6
```

To see which branch a tag belongs to in a clone, ask git: `git branch -r --contains 1.0.0.9` prints
`origin/1.0.0.x`, and the same for `1.0.0.10` prints `origin/master`.

## 3.2 composer create-project

`composer create-project` downloads a release, unpacks it into a new directory and runs `composer install` in it.
This is the way to start a site. The package name is always `se7enxweb/exponential-platform-nexus`; the version
selects the line, and **leaving it out installs upstream Media Site 3.1.6** (section 3.1). Where the release carries
a `composer.lock` (the 1.2.0.x and 1.3.0.x tags), `create-project` installs exactly the locked versions.

How to check, right after the command, that you got the line you meant:

```bash
cd nexus
composer show --self | head -2        # name: se7enxweb/exponential-platform-nexus (2.5, 1.0.0.x, 1.1.0.x),
                                       # __root__ (1.2.0.x, 1.3.0.x: their composer.json has no name);
                                       # netgen/media-site means you installed upstream
ls web/app.php 2>/dev/null && echo "2.5 generation" || ls public/index.php
php bin/console --version              # Symfony 3.4, 5.4 or 7.4
```

### 1.3.0.x

```bash
composer create-project se7enxweb/exponential-platform-nexus:1.3.0.5 nexus
cd nexus
```

To follow the line instead of a fixed release, use a constraint that floats the last number:
`se7enxweb/exponential-platform-nexus:~1.3.0.5` (meaning `>=1.3.0.5 <1.3.1.0`). Never `^1.3.0.5`, which reaches the
upstream `1.12.2` (section 3.1).

The lock file of `1.3.0.5` pins `se7enxweb/exponential-platform-dxp-core` at `v5.0.7`, whose installer does not find
the Netgen Layouts schema of the `se7enxweb/layouts-core` fork; `v5.0.9` (2026-10-05) fixes that. Update that one
package before you run the installer ([chapter 4](04-installing.md#47-the-130x-line)):

```bash
composer update se7enxweb/exponential-platform-dxp-core
```

### 1.2.0.x

```bash
composer create-project se7enxweb/exponential-platform-nexus:v1.2.0.0 nexus
```

### 1.1.0.x

```bash
composer create-project se7enxweb/exponential-platform-nexus:v1.1.0.7 nexus
```

The line's own `doc/INSTALL.md` uses the constraint `~1.1.0.1`, which resolves to the newest `v1.1.0.x`.

### 2.5 (master)

```bash
composer create-project --ignore-platform-reqs se7enxweb/exponential-platform-nexus:v2.5.0.6 nexus
```

`v2.5.0.6` predates the fixes of 2026-10-05 (chapter 1): its `web/app.php` still switches to `dev` for host names
containing `dev.`, its `AppCache` can make private pages public, and its build scripts lack the OpenSSL option current
Node.js needs. Until `v2.5.0.7` is tagged, a production site on this line is better installed from the `master`
branch (section 3.3), or must apply those changes itself
([chapter 6](06-serving-the-site.md#62-how-a-request-reaches-the-application-per-line)).

### 1.0.0.x

```bash
composer create-project --ignore-platform-reqs se7enxweb/exponential-platform-nexus:1.0.0.9 nexus
```

`1.0.0.9` is the newest tag cut from the `1.0.0.x` branch; `1.0.0.10` is newer by number but is `master` code.

Notes on the commands:

- **`--ignore-platform-reqs` on the 2.5 generation** is what the line's guide requires ([chapter 2](02-requirements.md#versions)).
  It disables every PHP and extension check; run `composer check-platform-reqs` afterwards and read what it reports.
- **`--keep-vcs`** keeps the `.git` directory of the release. Use it when you want to follow the upstream branch with
  `git pull` later; the 2.5 guide uses it with `composer install`.
- **The 2.5 generation asks questions.** Its post-install scripts run the Incenteev parameter handler, which creates
  `app/config/parameters.yml` from `parameters.yml.dist` and prompts for each value (database driver, host, name, user,
  password, the secret). Answer them, or pass `--no-interaction` and edit the file afterwards
  ([chapter 4](04-installing.md#43-the-25-line-master-v250x)).
- **Node.js and Yarn first on the 2.5 generation.** Its post-install scripts call `yarn install` and build the
  front-end assets ([chapter 2](02-requirements.md#25-nodejs-and-yarn)).
- **The scripts may fail on a machine without a database.** That is harmless: the code is on disk. Finish the
  configuration of [chapter 4](04-installing.md), then run `composer install` again in the project root, which repeats
  the scripts.
- **Minimum stability is `dev`.** Every line resolves some dependencies to branches. The same tag installed on two
  different days can give different `vendor/` contents; that is why [3.6](#36-the-lock-file-is-part-of-your-site)
  matters.

## 3.3 git clone and composer install

Clone the repository when you want to work on Nexus itself, follow a branch between releases (for example to get the
fixes of 2026-10-05 before their release), or install a five-part tag that Packagist does not offer.

**A clone without `-b` checks out `master`, the 2.5 generation**, whatever line you had in mind. Name the branch in
the clone command:

```bash
git clone -b 1.3.0.x https://github.com/se7enxweb/exponential-platform-nexus.git nexus   # or 1.2.0.x, 1.1.0.x, 1.0.0.x, master
cd nexus
git branch --show-current     # prints 1.3.0.x
# or a release: git checkout 1.3.0.5
composer install              # the 2.5 generation: composer install --keep-vcs --ignore-platform-reqs
```

If you already cloned without `-b`, `git checkout 1.3.0.x` before the first `composer install` is enough. After a
`composer install` on the wrong branch, check out the right one and run `composer install` again: `vendor/`,
`ezpublish_legacy/` and the files the Composer scripts created belong to the other generation, so remove `vendor/`
first or, simpler, start from a fresh clone.

| Line | Checkout | Install command (from the line's guide) |
|---|---|---|
| 2.5 | `master` (or `git checkout v2.5.0.6`) | `composer install --keep-vcs --ignore-platform-reqs` |
| 1.0.0.x | `1.0.0.x` (or a tag, for example `v1.0.0.0.3`) | `composer install --keep-vcs --ignore-platform-reqs` |
| 1.1.0.x | `1.1.0.x` | `composer install` |
| 1.2.0.x | `1.2.0.x` | `composer install` |
| 1.3.0.x | `1.3.0.x` | `composer install` |

The 1.2.0.x and 1.3.0.x branches commit a `composer.lock`; `composer install` installs exactly the versions in it. The
other branches have no lock file, so `composer install` resolves the newest versions the constraints allow, as
`composer update` would; on the 2.5 generation that means `se7enxweb/ezpublish-kernel` `v7.5.41` today, not whatever
7x tested at the time of the release.

A checkout of a branch tip has no release behind it. Note the commit you installed (`git rev-parse HEAD`) in your own
records, because nobody else can reproduce "1.3.0.x as of last Tuesday".

## 3.4 The packages each line pulls in

The `require` section of each line, reduced to the packages that define it:

| Package | 2.5 | 1.0.0.x | 1.1.0.x | 1.2.0.x | 1.3.0.x |
|---|---|---|---|---|---|
| Platform | `se7enxweb/ezpublish-kernel ~7.5.40` | same | `se7enxweb/oss ~3.3.0`, `se7enxweb/ezplatform-kernel ~1.3` | `se7enxweb/oss ~4.6.0` | `se7enxweb/exponential-platform-dxp dev-master` |
| Symfony | `se7enxweb/symfony v3.4.55` | same | `se7enxweb/symfony 5.4.x-dev`, `symfony/framework-bundle 5.4.*` | `symfony/framework-bundle 5.4.*` | `symfony/framework-bundle 7.4.*` |
| Legacy kernel | `se7enxweb/exponential ^6.0.12`, `se7enxweb/legacy-bridge ^2.1` | same | `se7enxweb/legacy-bridge ^3.0` | through `se7enxweb/site-legacy-bundle v2.0.0` | none |
| Site bundle | `se7enxweb/site-bundle ^1.7.4`, `se7enxweb/site-legacy-bundle ^1.4.5` | same | `se7enxweb/site-bundle ~2.1.5.1`, `se7enxweb/site-legacy-bundle ^2.0` | `se7enxweb/site-bundle ~3.0.6` | `se7enxweb/site-bundle 5.0.x-dev` |
| Demo data | `se7enxweb/cjw-exponential-media-site-data ^1.0` | `netgen/media-site-data ~1.8.1` | `se7enxweb/media-site-data ~2.2.5.2` | `netgen/media-site-data ^3.3` | `netgen/media-site-data ^4.0` |
| Installer bundle | `netgen/site-installer-bundle ^1.3` | same | `netgen/site-installer-bundle ^2.0` | `^3.1` | `^4.0` |
| Netgen Layouts | `netgen/layouts-standard ~1.4.0`, `se7enxweb/layouts-ezplatform ^1.4.11` | same | `netgen/layouts-standard ~1.4.0`, `netgen/layouts-ezplatform ~1.4.0` | `netgen/layouts-standard ~1.4.0`, `netgen/layouts-ibexa ~1.4.0` | `netgen/layouts-standard ~2.0.0`, `netgen/layouts-ibexa ~2.0.0` |
| Site API | `netgen/ezplatform-site-api ^3.7` | same | `se7enxweb/ezplatform-site-api ~4.3` | `netgen/ibexa-site-api ^6.1.2` | `netgen/ibexa-site-api ^7.0` |
| Admin | `se7enxweb/ezplatform-admin-ui ~1.5.33`, `se7enxweb/admin-ui-bundle ^2.9.15` | same | `se7enxweb/admin-ui-bundle ^3.0` | `se7enxweb/admin-ui-bundle ^4.0` | through the metapackage (`se7enxweb/admin-ui`) |

Versions resolved by the lock files that are committed: on 1.2.0.x `netgen/layouts-core 1.4.13`, `netgen/layouts-ibexa
1.4.16`, `netgen/ibexa-site-api 6.3.1`, `netgen/tagsbundle 5.3.1`, `netgen/media-site-data 3.3.0`,
`se7enxweb/site-bundle 3.0.6`, `symfony/http-kernel v5.4.51`; on 1.3.0.x `se7enxweb/exponential-platform-dxp-core
v5.0.7`, `se7enxweb/layouts-core dev-master`, `netgen/layouts-ui 2.0.0`, `netgen/ibexa-site-api 7.0.0`,
`netgen/tagsbundle 6.0.2`, `netgen/media-site-data 4.0.0`, `symfony/http-kernel v7.4.8`. The 1.3.0.x lock has not
moved since `1.3.0.3`: `1.3.0.3`, `1.3.0.4`, `1.3.0.5` and the branch tip lock the same core, `v5.0.7`, while
Packagist already offers `v5.0.9`.

### Forks replace upstream packages

Many of these packages are `se7enxweb/*` forks. Each declares the upstream name under `replace`, for example
`se7enxweb/site-bundle` with `"replace": {"netgen/site-bundle": "*"}`. Composer then installs only the fork, and every
package that requires `netgen/site-bundle` is satisfied by it. Two consequences:

- Do not add the upstream package next to the fork. The 2.5 line even declares `"conflict": {"netgen/site-bundle": "*"}`
  to stop that.
- Namespaces stay upstream. Code written for the same generation upstream runs unchanged.

On 1.3.0.x the forks `se7enxweb/layouts-core` and `se7enxweb/fieldtype-richtext` come in through the metapackage. When
Composer swaps between a fork and its upstream package, Symfony Flex may run the upstream package's "unconfigure"
recipe and remove `NetgenLayoutsBundle`, `NetgenLayoutsAdminBundle` and `config/routes/netgen_layouts.yaml`; the 7x guide
of the line calls this "the single most common source of breakage". After any Composer run on 1.3.0.x, check:

```bash
git diff --stat config/bundles.php config/routes/
```

and restore the files from git if those entries disappeared ([chapter 13](13-troubleshooting.md)).

## 3.5 What Composer runs after installing

| Line | Scripts after `composer install` and `composer update` |
|---|---|
| 2.5, 1.0.0.x | build `parameters.yml`, clear the cache, install bundle assets, install the legacy kernel's assets and the legacy extensions that bundles ship (`ezpublish:legacy:assets_install`, `ezpublish:legacybundles:install_extensions`), `ngsite:symlink:project`, `ngsite:symlink:legacy`, regenerate the legacy autoloads, dump the JavaScript translations, `yarn install`, compile the front-end assets, and a security check |
| 1.1.0.x | `bazinga:js-translation:dump`, `assets:install`, the two legacy asset and extension commands, then `ngsite:symlink:project`, `ngsite:symlink:legacy`, `bin/create_install_symlinks.php` (links the legacy kernel's `site.ini` overrides for `ngadminui` and `legacy_admin` to `src/install/`) and the legacy autoload generation |
| 1.2.0.x | `cache:clear`, `assets:install`, the two legacy asset and extension commands, `ngsite:symlink:project`, `ngsite:symlink:legacy`, the legacy autoload generation |
| 1.3.0.x | `cache:clear`, `assets:install`, `ngsite:symlink:project` |

On 1.1.0.x, 1.2.0.x and 1.3.0.x the administration interface's assets are not built by Composer. On 1.2.0.x and
1.3.0.x the separate Composer script `composer ibexa-assets` builds them; 1.1.0.x has no such script and builds them
with `yarn ez` after a translation dump. `make ibexa-assets` runs the right sequence on every line
([chapter 4](04-installing.md)). Only the 2.5 generation builds the site's own assets during `composer install`.

### The legacy directory is replaced on every update

On the lines with the legacy kernel, the plugin `se7enxweb/exponential-legacy-installer` places `se7enxweb/exponential`
in `ezpublish_legacy/` (the `extra.ezpublish-legacy-dir` of `composer.json`). It replaces that directory whenever the
package is updated. Anything you keep inside it, such as uploaded files in `ezpublish_legacy/var/` or your own
settings, is lost. That is why the projects keep these outside and link them in: the 2.5 generation's guide creates
symlinks for the `app` legacy extension and the storage directory from `src/AppBundle/ezpublish_legacy/`, and 1.1.0.x
links its legacy settings from `src/install/` ([chapter 4](04-installing.md)).

## 3.6 The lock file is part of your site

Because several requirements are branches, `composer.lock` is the only exact description of what your site runs.

- Commit `composer.lock` to your site's own repository.
- Deploy with `composer install` (which follows the lock file), never `composer update`, on servers.
- Run `composer update` on a development copy, test, then commit the new lock file.
- Note the Nexus tag or commit you started from; [chapter 11](11-upgrading-between-lines.md) needs it.

## 3.7 A tour of the project root

### The 2.5 generation

| Entry | What it is |
|---|---|
| `app/` | `AppKernel.php` (bundle registration), `AppCache.php` (the reverse proxy cache), `app/config/` with `config.yml`, `config_<env>.yml`, `parameters.yml.dist`, `default_parameters.yml`, `ezplatform.yml`, `ezplatform_siteaccess.yml`, `cache_pool/` |
| `bin/` | `console`, `symfony_requirements`, `vhost.sh` (generates a virtual host from `doc/apache2/vhost.template`) |
| `src/AppBundle/` | the project bundle: `Resources/config/` (services, Layouts blocks, views, image variations), `Resources/views/`, Sass and ES6 sources, translations, `Installer/` (1.0.0.x), `ezpublish_legacy/` (the legacy extension `app` and the storage directory that the install links in), `Resources/database/sql/` (1.0.0.x: the CJW SQL dumps) |
| `web/` | the document root: `app.php`, `app_dev.php`, `.htaccess`, `assets/` (built assets, committed under `web/assets/app/build*`), `bundles/` |
| `ezpublish_legacy/` | the legacy kernel, written by Composer |
| `var/` | `cache/`, `logs/`, sessions; on 1.0.0.x the SQLite file `var/data_<env>.db` |
| `config/`, `public/`, `templates/` | also present on `master` and `1.0.0.x`, carried over from the newer lines; the 2.5 application does not read them (it uses `app/` and `web/`) |
| `doc/` | documentation: this book, `INSTALL.md`, `apache2/`, `nginx/`, `varnish/`, `docker/`, `netgen/`, `cjw/` |
| `package.json`, `webpack.config.js`, `webpack.config.default.js`, `webpack.config.ezplatform.js` | the front-end build: the site build, and the admin build renamed to `webpack.config.ezplatform.js` |
| `ngsite.cron` | the cron entries of the Media Site |

### 1.1.0.x, 1.2.0.x and 1.3.0.x

| Entry | What it is |
|---|---|
| `.env`, `.env.dev`, `.env.test`, `.env.local.dist` | environment defaults; `.env.local` (yours, ignored by `.gitignore`) overrides them |
| `LICENSE.md`, `LICENSE`, `COPYRIGHT`, `LICENSE-bul` | 1.1.0.x and 1.2.0.x: the GPL text in `LICENSE.md` plus the upstream skeleton's files; 1.3.0.x: only `COPYRIGHT` and `LICENSE-bul` (see [chapter 1](01-introduction.md#11-what-exponential-platform-nexus-is)) |
| `.nvmrc` | the Node.js version for `nvm use` |
| `assets/` | Sass and JavaScript of the site designs |
| `bin/console` | the Symfony console; on 1.1.0.x also `bin/create_install_symlinks.php` |
| `config/bundles.php` | the registered bundles |
| `config/packages/` | configuration per package (Doctrine, Layouts, HTTP cache, security, JWT and so on) |
| `config/routes/` | routes, including `netgen_layouts.yaml` |
| `config/app/` | the Media Site configuration: `packages/` (siteaccesses, views, image variations), `prepends/` (Layouts blocks and views), `services.yaml`, `server/` (per-server parameters selected by `SERVER_ENVIRONMENT`) |
| `config/jwt/` | the REST API's JWT key pair, generated during the install (not committed) |
| `data/` | 1.1.0.x and 1.2.0.x: `sqlite/media_schema.sql` and `{mysql,postgresql,sqlite}/media_data.sql` for `exponential-media` |
| `src/` | `Kernel.php`, controllers, entities; the install command and installer on 1.1.0.x (`src/Command/`, `src/Installer/`) and 1.2.0.x (`src/RepositoryInstaller/`); the `exponential-oss` installer on 1.3.0.x (`src/Installer/`) |
| `templates/` | Twig: `themes/app`, `themes/fh` (Fit & Healthy), `themes/bold` (Bold Agency), `themes/common`, `nglayouts/`, `bundles/` (overrides of bundle templates) |
| `translations/` | the site's translation files |
| `migrations/` | Doctrine migrations |
| `public/` | the document root: `index.php`, `.htaccess`, `assets/` (built), `bundles/`, `var/site/storage/` (uploaded and demo images) |
| `ezpublish_legacy/` | the legacy kernel (1.1.0.x and 1.2.0.x) |
| `var/` | `cache/`, `log/`, `sessions/`, the SQLite file `var/data_<env>.db` |
| `Makefile` | shortcuts (`make build`, `make assets`, `make ibexa-assets`, `make images`, `make reindex`) |
| `compose.yaml`, `deploy.php`, `deploy/` | a Docker Compose file and a Deployer recipe from upstream |
| `webpack.config.js` and friends | the front-end builds |

## 3.8 Permissions after getting the code

The web server's PHP user must be able to write `var/` and the document root's `var/` (`public/var` or `web/var`), and,
on the lines with the legacy kernel, `ezpublish_legacy/var/`. The upstream notes use ACLs:

```bash
setfacl -R -m u:<web-user>:rwX -m g:<web-user>:rwX var public/var
setfacl -dR -m u:<web-user>:rwX -m g:<web-user>:rwX var public/var
```

(`web/var` instead of `public/var` on the 2.5 generation, and add `ezpublish_legacy/var` where it exists.) Without
`setfacl`, follow [Symfony's file permissions page](https://symfony.com/doc/current/setup/file_permissions.html).
[Chapter 4](04-installing.md) repeats this at the point in the install where it matters, and
[chapter 14](14-security-hardening.md) explains what must stay read-only.

## 3.9 Sister repositories

| Repository | What it is |
|---|---|
| [se7enxweb/exponential-platform-nexus-starter](https://github.com/se7enxweb/exponential-platform-nexus-starter) | a ready-to-run copy of the 5 generation, configured for SQLite; the reference installation behind this book was made from it |
| [se7enxweb/cjw-exponential-media-site-data](https://github.com/se7enxweb/cjw-exponential-media-site-data) | the CJW demo data ("JAC Example") for the `cjw-exponential-media` installer type of the 2.5 line; Packagist `v1.0.0`, `v1.0.1` |
| [netgen/media-site-data](https://github.com/netgen/media-site-data) | Netgen's demo data and images (Fit & Healthy, Bold Agency) |
| [se7enxweb/exponential-platform-dxp](https://github.com/se7enxweb/exponential-platform-dxp) | the metapackage of the 5 generation |
| [se7enxweb/oss](https://github.com/se7enxweb/oss) | the metapackage of the 3.3 and 4.6 generations |
| [se7enxweb/exponential-platform-legacy](https://github.com/se7enxweb/exponential-platform-legacy) | Exponential Platform Legacy ([chapter 1](01-introduction.md#14-nexus-exponential-6-and-exponential-platform-legacy)) |
| [se7enxweb/exponential](https://github.com/se7enxweb/exponential) | Exponential 6, the legacy kernel |

## References

In this repository:

- The 7x guide of each line (`doc/sevenx/INSTALL.md` on 1.1.0.x to 1.3.0.x, `doc/INSTALL.md` on `1.0.0.x` and in
  `v2.5.0.6`) and [the short guide](../INSTALL.md).
- [doc/netgen/INSTALL.md](../netgen/INSTALL.md): the upstream notes on `create-project` and contribution clones.
- `composer.json` and, on 1.2.0.x and 1.3.0.x, `composer.lock` of the branch you install.

External:

- Composer: [create-project](https://getcomposer.org/doc/03-cli.md#create-project), [install](https://getcomposer.org/doc/03-cli.md#install-i),
  [check-platform-reqs](https://getcomposer.org/doc/03-cli.md#check-platform-reqs), [versions and constraints](https://getcomposer.org/doc/articles/versions.md).
- Packagist: [se7enxweb/exponential-platform-nexus](https://packagist.org/packages/se7enxweb/exponential-platform-nexus),
  and the metadata Composer reads: [tagged versions](https://repo.packagist.org/p2/se7enxweb/exponential-platform-nexus.json),
  [branches](https://repo.packagist.org/p2/se7enxweb/exponential-platform-nexus~dev.json);
  [se7enxweb/exponential-platform-dxp-core](https://packagist.org/packages/se7enxweb/exponential-platform-dxp-core) (the 1.3.0.x core).
- Composer: [the tilde and caret operators](https://getcomposer.org/doc/articles/versions.md#next-significant-release-operators).
- Symfony: [Flex and recipes](https://symfony.com/doc/current/setup/flex.html), [file permissions](https://symfony.com/doc/current/setup/file_permissions.html).
- Upstream install pages: [2.5](https://doc.ibexa.co/en/2.5/getting_started/install_ez_platform/),
  [3.3](https://doc.ibexa.co/en/3.3/getting_started/install_ez_platform/), [4.6](https://doc.ibexa.co/en/4.6/getting_started/install_ibexa_dxp/),
  [5.0](https://doc.ibexa.co/en/5.0/getting_started/install_ibexa_dxp/).
- The [Exponential 6 book, chapter 3](https://github.com/se7enxweb/exponential/blob/main/doc/install/03-getting-the-code.md).

[Contents](README.md) · Previous: [2. Requirements](02-requirements.md) · Next: [4. Installing](04-installing.md)
