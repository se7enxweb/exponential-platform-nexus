# 10. Operations

An installed Exponential Platform Nexus site keeps working only if somebody looks after it: caches are cleared and
warmed after a change, the HTTP cache is purged when content changes in ways the repository cannot see, scheduled
tasks run, the search index follows the content, image variations are generated and cleaned, logs are rotated before
they fill the disk, and the database and the binary files are backed up in a way that can actually be restored. This
chapter is the operator's handbook for all four lines. It names the exact console commands of each line (they differ:
`ezplatform:*` on 1.0.0.x, `exponential:*` with the old names as aliases on 1.1.0.x, `ibexa:*` on 1.2.0.x, and partly `exponential:*` on 1.3.0.x), the files
those commands read and write, and the order in which a deploy should run them, with Exponential Velocity first.

[Previous: 9. Front end and themes](09-frontend-and-themes.md) · [Next: 11. Upgrading between lines](11-upgrading-between-lines.md) ·
[Contents](README.md)

---

## Contents of this chapter

1. [The operator's map per line](#101-the-operators-map-per-line)
2. [Symfony caches: clear and warm up](#102-symfony-caches-clear-and-warm-up)
3. [The persistence cache pool](#103-the-persistence-cache-pool)
4. [HTTP cache and purging](#104-http-cache-and-purging)
5. [Scheduled tasks: cron](#105-scheduled-tasks-cron)
6. [Background workers: Messenger](#106-background-workers-messenger)
7. [Search: legacy search engine and Solr](#107-search-legacy-search-engine-and-solr)
8. [Images and image variations](#108-images-and-image-variations)
9. [Logs](#109-logs)
10. [Backups and restore](#1010-backups-and-restore)
11. [Performance](#1011-performance)
12. [Deploying a change](#1012-deploying-a-change)
13. [Checklist](#1013-checklist)
14. [References](#1014-references)

Every command in this chapter is run from the project root, the directory that holds `composer.json` and `bin/console`,
as the user the web server or Exponential Velocity runs PHP as. Running the console as `root` leaves files in `var/`
that the web user cannot write afterwards (see [10.2.3](#1023-ownership-after-a-clear)). Add `--env=prod` to every
console command on a production system; the examples spell it out where it matters.

---

## 10.1 The operator's map per line

The four lines share the Symfony console and most of the Netgen tooling, but the names of the platform commands, the
environment variable that selects the environment and the directories differ. The table is the short version of this
chapter; every row is explained in the sections that follow.

| | 1.0.0.x (Platform 2.5, Symfony 3.4) | 1.1.0.x (3.3, Symfony 5.4) | 1.2.0.x (Ibexa OSS 4.6, Symfony 5.4) | 1.3.0.x (v5, Symfony 7.4) |
|---|---|---|---|---|
| Console | `bin/console` | `bin/console` | `bin/console` | `bin/console` |
| Environment selected by | `SYMFONY_ENV` (default `dev` in `bin/console`, `prod` in `web/app.php`) | `APP_ENV` in `.env` / `.env.local` | `APP_ENV` | `APP_ENV` |
| Front controller (document root) | `web/app.php` (`web/`) | `public/index.php` (`public/`) | `public/index.php` | `public/index.php` |
| Symfony cache | `var/cache/<env>/` | `var/cache/<env>/` | `var/cache/<env>/` | `var/cache/<env>/` |
| Logs | `var/logs/` | `var/log/` | `var/log/` | `var/log/` |
| Binary files (images, files) | `web/var/site/storage/` | `public/var/site/storage/` | `public/var/site/storage/` | `public/var/site/storage/` |
| Cron runner | `ezplatform:cron:run` | `ibexa:cron:run` (alias `ezplatform:cron:run`) | `ibexa:cron:run` (alias `ezplatform:cron:run`) | `ibexa:cron:run` |
| Reindex | `ezplatform:reindex` | `exponential:reindex` (aliases `ibexa:reindex`, `ezplatform:reindex`; see below) | `ibexa:reindex` (alias `ezplatform:reindex`); the project adds `exponential:reindex` | `exponential:reindex` |
| Legacy kernel (`ezpublish_legacy/`) | yes, through the legacy bridge | yes | yes | no |
| Messenger configured | no | no | `config/packages/messenger.yaml`, no transport | `sync://` transport plus the Ibexa Messenger bundle |

Where the reindex names come from, per line:

- **1.0.0.x**: the 2.5 kernel, `setName('ezplatform:reindex')`.
- **1.1.0.x**: the line has no lock file, so the kernel is whatever `se7enxweb/ezplatform-kernel ~1.3` resolves to.
  Its tags `v1.3.43` to `v1.3.45` register `exponential:reindex` with `ibexa:reindex` and `ezplatform:reindex` as
  deprecated aliases (an older installation of the 3.3 kernel answers to `ibexa:reindex` with the `ezplatform:`
  alias). The project also ships its own `exponential:reindex` (`src/Command/ExponentialReindexCommand.php`), a proxy
  that only takes `--siteaccess` and runs `ibexa:reindex`. Two commands with one name: the console keeps one of them.
  `php bin/console exponential:reindex --help` tells you which: if it lists only `--siteaccess`, the proxy answers,
  and the kernel's command with all the options of [10.7.2](#1072-reindexing) is reachable as `ibexa:reindex`.
- **1.2.0.x**: the lock file installs the upstream kernel `ibexa/core` 4.6, whose command is `ibexa:reindex` (alias
  `ezplatform:reindex`). The project adds the same proxy, `exponential:reindex`
  (`src/RepositoryInstaller/Command/ExponentialReindexCommand.php`), with only `--siteaccess`.
- **1.3.0.x**: the kernel `se7enxweb/exponential-platform-dxp-core` v5.0.7, `#[AsCommand(name: 'exponential:reindex')]`
  with no `ibexa:reindex` alias.

The cron runner kept its upstream name on every line (`ezplatform:cron:run` on 2.5, `ibexa:cron:run` later). When
in doubt on your own installation, `php bin/console list ibexa`, `list ezplatform` and `list exponential` show what
is there.

Chapter [8. Configuration](08-configuration.md) explains the environments, the `.env` files and where each setting
lives; this chapter assumes them.

## 10.2 Symfony caches: clear and warm up

### 10.2.1 What is cached where

Three different things are called "the cache" on a Nexus site, and they are cleared in three different ways:

| Cache | Lives in | Cleared by | Needed after |
|---|---|---|---|
| Symfony's compiled container, routes, Twig templates, translations | `var/cache/<env>/` | `cache:clear` | every change of code, configuration, `.env`, templates or translations in `prod` |
| The repository's persistence cache (content, locations, URL aliases, permissions) | the pool named by `CACHE_POOL` (section [10.3](#103-the-persistence-cache-pool)) | `cache:pool:clear <pool>`, or automatically by the repository on every write | a direct database change, a restore, an import that bypasses the API |
| Rendered pages | the HTTP cache: the platform's local proxy (stored in `var/cache/<env>/http_cache/`), Varnish, or Velocity's response cache (section [10.4](#104-http-cache-and-purging)) | a purge (tags, paths, ban) | changes the repository cannot see: templates, configuration |

`cache:clear` clears the first, and the local proxy's store with it, but neither the pool nor Varnish. A site that "still shows the old page" after a `cache:clear` usually has a full
HTTP cache or a stale persistence cache, not a stale container.

### 10.2.2 `cache:clear` and `cache:warmup`

```bash
# development: rebuilt on demand, clearing is rarely needed
php bin/console cache:clear

# production: clear, then warm before the first visitor arrives
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
```

On 1.1.0.x and later, `cache:clear` warms the cache itself unless `--no-warmup` is given, so the second command only
matters after `cache:clear --no-warmup` (which the 1.0.0.x Composer scripts use) or after a deploy tool cleared the
directory by hand. Deployer does both in its own steps (`deploy:cache:clear`, `deploy:cache:warmup`).

On 1.0.0.x the environment comes from `--env` or `SYMFONY_ENV`; `bin/console` falls back to `dev`, while
`web/app.php` falls back to `prod`. A `cache:clear` without `--env=prod` on a 1.0.0.x production server therefore
clears the wrong directory. A project patch also affects it: `bin/console` and `web/app.php` load a `.env.php` file
from the project root when it exists, so a `putenv('SYMFONY_ENV=...')` there decides for both. Installations made
from the releases up to `v2.5.0.6` and `1.0.0.10` also switch to `dev` for any host name that contains `dev.`; the
branches removed that on 5 October 2026 ([chapter 14.4](14-security-hardening.md#144-debug-mode-and-the-environment)).
Check both before you wonder which environment a request ran in:

```bash
ls -l .env.php 2>/dev/null; grep -n "dev\." web/app.php      # 1.0.0.x
ls -d var/cache/*/                                            # the environments that have built a cache
```

The cache directory can be large in `dev`: the reference v5 installation holds 2.8 GB in `var/cache/dev/`. Production
caches are much smaller and should not be cleared on every request-path problem; clear them when the code or
configuration changed.

### 10.2.3 Ownership after a clear

`cache:clear` writes the new cache as the user who runs it. If that is not the user PHP runs as under Exponential
Velocity, PHP-FPM or Apache, the next request fails to write into `var/cache/prod/` and the site answers 500. Run the
console as the web user:

```bash
sudo -u <web-user> php bin/console cache:clear --env=prod
```

or repair ownership afterwards (`chown -R <web-user>:<web-group> var/`). Chapter
[6. Serving the site](06-serving-the-site.md) describes the users, groups and permissions per server.

### 10.2.4 The legacy kernel's caches (1.0.0.x to 1.2.0.x)

The lines that carry the legacy bridge have a second set of caches inside `ezpublish_legacy/var/`, managed by the
legacy kernel. They are not touched by `cache:clear`. The bridge runs legacy scripts through the Symfony console, in
the same form the Composer scripts of those lines use for `ezpgenerateautoloads.php`:

```bash
php bin/console ezpublish:legacy:script bin/php/ezcache.php --clear-all --env=prod
```

Clear them after changing legacy INI settings, legacy templates or legacy extensions. The 1.3.0.x line has no legacy
kernel.

## 10.3 The persistence cache pool

The repository caches what it reads from the database in a Symfony cache pool. The pool is chosen at container build
time from the `CACHE_POOL` environment variable: the kernel loads `config/packages/cache_pool/<CACHE_POOL>.yaml`
(`app/config/cache_pool/<CACHE_POOL>.yml` on 1.0.0.x, through `app/config/env/generic.php`) and points
`cache_service_name` at it. Because it is read at build time, **changing `CACHE_POOL` requires a `cache:clear`**.

| `CACHE_POOL` | Adapter | When to use |
|---|---|---|
| `cache.tagaware.filesystem` (the default in every line's `.env`) | `FilesystemTagAwareAdapter` under `var/cache/<env>/` | one server, the default; also what the reference installation runs |
| `cache.redis` | `RedisTagAwareAdapter`, connection `redis://%cache_dsn%` | several servers sharing one cache, or a large site where filesystem tag invalidation becomes slow |
| `cache.memcached` | memcached adapter | shipped as a file; Ibexa recommends Redis over memcached for tag-aware caching |
| `cache.apcu` | APCu (1.0.0.x only) | single server, small sites |

The Redis pool reads `CACHE_DSN` (host and port, or a full DSN such as `secret@redis.example.com:6379/2`) and
`CACHE_NAMESPACE` (default `ibexa`; `ez` on 1.0.0.x). Give every installation that shares a Redis server its own
namespace or database number; two sites with the same namespace serve each other's content.

```dotenv
# .env.local on each web server
CACHE_POOL=cache.redis
CACHE_DSN=redis.internal:6379/3
CACHE_NAMESPACE=nexus_prod
```

The repository invalidates its own entries on every write through the API. Clear the pool by hand only after a
direct database change, a restore or a migration that wrote SQL:

```bash
php bin/console cache:pool:clear cache.tagaware.filesystem --env=prod   # or cache.redis
```

The `Makefile` of every line has a `clear-all-cache` target that runs `cache:clear` and then
`cache:pool:clear $(CACHE_POOL)`. Since 5 October 2026 the branches set `CACHE_POOL ?= cache.global_clearer`, which
empties every pool the application defines, whichever adapter it uses:

```bash
make clear-all-cache APP_ENV=prod                              # every pool
make clear-all-cache APP_ENV=prod CACHE_POOL=cache.redis       # one pool
```

Up to then (every release tag) the file said `CACHE_POOL = cache.redis`; on an installation with the default
filesystem pool that service does not exist and the target stopped with an error naming the missing service
`cache.redis`. On such a checkout pass the pool: `make clear-all-cache APP_ENV=prod CACHE_POOL=cache.global_clearer`.
Because the new line uses `?=`, a `CACHE_POOL` exported in the shell (for example for Symfony) now also decides which
pool `make` clears; unset it or pass the pool on the command line.

## 10.4 HTTP cache and purging

### 10.4.1 How the HTTP cache is wired

Every line ships the platform's HTTP cache bundle (`ezplatform-http-cache` up to 1.1.0.x, `ibexa/http-cache` on
1.2.0.x and 1.3.0.x) on top of FOSHttpCacheBundle. Responses carry cache tags (`xkey` / `X-Cache-Tags`); when content
is published, moved, hidden or deleted, the bundle purges the tags that the change affects. Where the purge goes is
the **purge type**:

| `HTTPCACHE_PURGE_TYPE` | Purges | Notes |
|---|---|---|
| `local` (the default; `env(HTTPCACHE_PURGE_TYPE): local` in `config/packages/ibexa.yaml`) | Symfony's HttpCache store on the same server | needs the kernel to be wrapped in the platform's `AppCache` |
| `varnish` (also `http`) | the Varnish servers in `HTTPCACHE_PURGE_SERVER` | one or more servers; `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` where an IP ACL is not possible |

Like `CACHE_POOL`, the purge type is set at container build time; clear the Symfony cache after changing it. Other
variables: `HTTPCACHE_DEFAULT_TTL` (default `86400` seconds, the `default_ttl` of content responses) and
`TRUSTED_PROXIES` (`127.0.0.1` in the shipped `.env`), which must list Varnish and any load balancer so that the
client address and `https` are recognised. The framework configuration reads `TRUSTED_PROXIES` since 5 October 2026
(the branch heads and `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6`); on older releases nothing reads it until you add the setting
([chapter 14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies)).

### 10.4.2 The local proxy per line

- **1.0.0.x.** `web/app.php` wraps the kernel in `AppCache` unless `SYMFONY_HTTP_CACHE` is set to `0`; in any
  environment but `dev` it is on by default. The project's `app/AppCache.php` extends the platform's class and
  adjusts `Cache-Control` after the response is built. Since 5 October 2026 (the branches, `v2.5.0.7` and `1.0.0.11`) it never makes a private
  or personal response public, keeps the administration siteaccesses, Layouts, GraphQL, the Content Browser, the REST
  API and the user pages out of the shared cache whatever the configuration says, and reads
  `app/config/http_cache.yml` (host names, siteaccesses and URL patterns that stay uncached) plus the
  `HTTP_CACHE_UNCACHED_HOSTNAMES` environment variable. **Edit the `uncached_hostnames` list for your own host
  names**: the shipped file names the project's demonstration hosts. The class of the releases up to `v2.5.0.6` and
  `1.0.0.10` rewrote private responses to public and never read that file; [chapter 14.7](14-security-hardening.md#147-http-cache-safety)
  says how to tell which one you have and how to replace it. With Varnish in front, set `SYMFONY_HTTP_CACHE=0`.
- **1.1.0.x, 1.2.0.x and 1.3.0.x, branch heads and `v1.1.0.8`, `v1.2.0.1` and `1.3.0.6`.** Since 5 October 2026 (commits `7f8d95f29` on `1.1.0.x`,
  `c5e6aca42` on `1.2.0.x`, `ac3f43a74` on `1.3.0.x`) `public/index.php` wraps the kernel in the platform's
  `AppCache` and enables HTTP method override when `APP_HTTP_CACHE` is true. It stays off when the variable is unset,
  empty or `0`, which is the shipped state. Turn it on for a site without Varnish:

  ```dotenv
  # .env.local
  APP_HTTP_CACHE=1
  HTTPCACHE_PURGE_TYPE=local
  ```

  After `cache:clear` and a reload of PHP (or Velocity), a second request for the same anonymous page is answered
  from `var/cache/<env>/http_cache/` without rendering; with debug on, the `X-Symfony-Cache` response header shows
  `fresh` or `stale` (it is not sent with debug off). Leave it off when Varnish does the caching: two caches in a
  row purge only the inner one.
- **1.1.0.x to 1.3.0.x, older releases** (`v1.1.0.7`, `v1.2.0.0`, `1.3.0.5` and older). `public/index.php` is the plain
  Symfony Runtime front controller and ignores `APP_HTTP_CACHE`. With `HTTPCACHE_PURGE_TYPE=local` and no Varnish
  there is no reverse proxy in front of PHP at all: responses carry cache headers, browsers honour them, and purges
  go to a store that serves nothing. This is safe, but every page is rendered by PHP. Put Varnish in front
  (chapter [6](06-serving-the-site.md)), or take `public/index.php` from a newer release of your line. The `README.md`
  of these lines lists "Symfony HttpCache (default)"; on the older releases that is not what the front controller does.

### 10.4.3 Varnish

The VCL that matches the HTTP cache bundle is shipped with the bundle, not with the project: on 1.2.0.x and 1.3.0.x in
`vendor/ibexa/http-cache/docs/varnish/vcl/` (`varnish5.vcl`, `varnish6.vcl`, `varnish7.vcl` and `parameters.vcl`), on
1.0.0.x additionally in the project's own [`doc/varnish/`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/varnish/varnish.md). Use the file for your Varnish
version, set the backend and the purge ACL in `parameters.vcl`, and point the platform at Varnish:

```dotenv
HTTPCACHE_PURGE_TYPE=varnish
HTTPCACHE_PURGE_SERVER=http://127.0.0.1:6081
TRUSTED_PROXIES=127.0.0.1
```

### 10.4.4 Purging by hand

The repository purges what it knows about. Purge by hand after a deploy that changed templates, after editing
configuration that changes output, and after a restore:

```bash
# everything the platform tagged (the tag every content response carries)
php bin/console fos:httpcache:invalidate:tag ez-all --env=prod

# a single path
php bin/console fos:httpcache:invalidate:path /some/page --env=prod
```

`ez-all` is the tag the bundle adds to every response (`ContentTagInterface::ALL_TAG` in `ibexa/http-cache`; the
local purge client invalidates the same tag), and it is the tag the Deployer task `httpcache:invalidate` purges after
a deploy. The `fos:httpcache:*` commands go through FOSHttpCache's proxy client, which the HTTP cache bundle
configures as a Varnish client sending `PURGE` requests to `HTTPCACHE_PURGE_SERVER`: they are the tool for Varnish.
With the local proxy, the store lives in the Symfony cache directory (`%kernel.cache_dir%/http_cache`, that is
`var/cache/<env>/http_cache/`), so `cache:clear` empties it as well.

The command `fos:httpcache:invalidate:path / --all` quoted in the `README.md` and `doc/sevenx/INSTALL.md` of the
1.1.0.x to 1.3.0.x lines up to `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5` (corrected since) does not exist in that form: `invalidate:path` takes only paths, and has no `--all` option.
With Varnish, a ban by host (`varnishadm "ban req.http.host ~ www.example.com"`, the Deployer task `varnish:ban`) is
the blunt alternative.

### 10.4.5 Netgen Layouts

Netgen Layouts invalidates the HTTP cache itself when a layout or a block is published: its
`netgen_layouts.http_cache.invalidation` setting is enabled by default, and it uses the same FOSHttpCache client.
No extra step is needed with Varnish. With no proxy, there is nothing to invalidate.

### 10.4.6 Velocity's response cache

Exponential Velocity has its own response cache in front of the workers. It is **off unless a loaded configuration
says `enabled: true`**, and it does not receive the platform's tag purges: it is told to forget everything by touching
its generation marker (`qbixconsole cache:clear`). For a Nexus site, leave it off and let the platform's HTTP cache
(Varnish) do the work, or enable it only together with a deploy step and a publish hook that bump the generation;
otherwise an edited page stays stale until its TTL runs out. Chapter [6](06-serving-the-site.md) covers the setting.

## 10.5 Scheduled tasks: cron

### 10.5.1 The platform's cron runner

All lines carry the platform's cron bundle and its runner command. It runs the jobs that bundles register under a
category (`--category`, default `default`):

```bash
php bin/console ezplatform:cron:run --env=prod    # 1.0.0.x
php bin/console ibexa:cron:run --env=prod         # 1.1.0.x, 1.2.0.x, 1.3.0.x
```

On the installations built from each line, no bundle registers a cron job (no service carries the
`ezplatform.cron.job` or `ibexa.cron.job` tag), so the runner does nothing out of the box. Install it in the crontab
anyway: bundles added later expect it, and a runner that does nothing costs a PHP start every few minutes.

### 10.5.2 The legacy cron jobs (1.0.0.x to 1.2.0.x)

The legacy kernel has its own cron scripts (`runcronjobs.php` with the parts `frequent`, `infrequent` and others).
The 1.0.0.x line ships a ready crontab file, [`ngsite.cron`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/ngsite.cron), that runs them through the bridge:

```cron
EZPUBLISHROOT=/path/to/the/project
EZPUBLISHSCRIPT=bin/console ezpublish:legacy:script runcronjobs.php
SITEACCESS=ngadminui
PHP=/usr/bin/php

35 6 * * *   cd $EZPUBLISHROOT && $PHP $EZPUBLISHSCRIPT -q --siteaccess $SITEACCESS 2>&1
20 5 * * 1   cd $EZPUBLISHROOT && $PHP $EZPUBLISHSCRIPT -q --siteaccess $SITEACCESS infrequent 2>&1
*/15 * * * * cd $EZPUBLISHROOT && $PHP $EZPUBLISHSCRIPT -q --siteaccess $SITEACCESS frequent 2>&1
*/15 * * * * cd $EZPUBLISHROOT && $PHP $EZPUBLISHSCRIPT -q --siteaccess $SITEACCESS ezplatformindexsubtree 2>&1
*/5 * * * *  cd $EZPUBLISHROOT && $PHP $EZPUBLISHSCRIPT -q --siteaccess $SITEACCESS ezflow 2>&1
```

Set `EZPUBLISHROOT` to the project root, keep `SITEACCESS` on an administration siteaccess, and, as the file's own
comment says, add `--env=prod` after `bin/console` in production (`EZPUBLISHSCRIPT=bin/console --env=prod
ezpublish:legacy:script runcronjobs.php`). Install it for the web user (`crontab -u <web-user> ngsite.cron`, or copy
the lines into that user's crontab). The 1.1.0.x and 1.2.0.x lines still carry the legacy kernel but no
`ngsite.cron`; the same lines work there with your own legacy administration siteaccess. The legacy cron parts are
documented in the Exponential 6 book, [chapter 10](https://github.com/se7enxweb/exponential/blob/main/doc/install/10-after-installing.md).

### 10.5.3 Scheduled visibility (1.2.0.x, 1.3.0.x)

Both newer lines require `netgen/ibexa-scheduled-visibility`, which hides and reveals content by publish and
expiry dates. It is disabled by default (`netgen_ibexa_scheduled_visibility.enabled: false`). Once you enable it, its
documentation requires a cron job:

```cron
*/5 * * * * cd /path/to/project && php bin/console ngscheduledvisibility:update --no-interaction --env=prod
```

### 10.5.4 A complete crontab

For a 1.3.0.x site, as the web user:

```cron
MAILTO=""
*/5 * * * * cd /path/to/project && php bin/console ibexa:cron:run --env=prod >> var/log/cron.log 2>&1
# only when scheduled visibility is enabled:
*/5 * * * * cd /path/to/project && php bin/console ngscheduledvisibility:update --no-interaction --env=prod >> var/log/cron.log 2>&1
```

For 1.0.0.x, use `ezplatform:cron:run` and add the legacy lines of [10.5.2](#1052-the-legacy-cron-jobs-100x-to-120x).
Rotate `var/log/cron.log` with the other logs ([10.9](#109-logs)).

## 10.6 Background workers: Messenger

None of the lines needs a long-running worker out of the box:

- **1.0.0.x and 1.1.0.x** have no Messenger configuration.
- **1.2.0.x** has `config/packages/messenger.yaml` with `reset_on_message: true` and no transport and no routing:
  every message is handled synchronously.
- **1.3.0.x** defines a `sync://` transport with no routing, and registers `IbexaMessengerBundle`. That bundle
  provides its own transport, `ibexa.messenger.transport`, whose default DSN is a Doctrine table
  (`doctrine://ibexa.current?table_name=ibexa_messenger_messages&auto_setup=false`), and its own bus,
  `ibexa.messenger.bus`. Nothing in the open-source packages of the reference installation dispatches to it.

The `.env` of 1.1.0.x and later sets `MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0`, which takes effect only
if you route messages to an `async` transport that uses it.

When you or a bundle start sending messages to a queue, run a worker under a process manager (systemd or
Supervisor), with limits so a leaking worker is replaced:

```bash
# your own transport
php bin/console messenger:consume async --env=prod --limit=1000 --time-limit=3600 --memory-limit=256M

# the Ibexa Messenger transport (1.3.0.x), as documented by Ibexa
php bin/console messenger:consume ibexa.messenger.transport --bus=ibexa.messenger.bus --env=prod \
    --limit=100 --time-limit=60 --memory-limit=256M
```

`auto_setup` is off in both DSNs, so the queue table must exist before the first message: create it with
`messenger:setup-transports` or through your migrations. After every deploy, stop the workers so they restart with
the new code: `php bin/console messenger:stop-workers --env=prod`.

## 10.7 Search: legacy search engine and Solr

### 10.7.1 Choosing the engine

`SEARCH_ENGINE` selects the repository's search engine; every line defaults to `legacy`, which searches the SQL
database directly and needs no extra service. `solr` uses Apache Solr through the platform's Solr bundle, configured
by `SOLR_DSN` (default `http://localhost:8983/solr`) and `SOLR_CORE` (default `collection1`), and the
`config/packages/ibexa_solr.yaml` (1.2.0.x, 1.3.0.x) or `ezplatform_solr.yaml` (1.1.0.x) file. The reference
installation runs `legacy`.

Like the other compile-time switches, a change of `SEARCH_ENGINE` needs a `cache:clear` and then a full reindex.

### 10.7.2 Reindexing

| Line | Command |
|---|---|
| 1.0.0.x | `php bin/console ezplatform:reindex --env=prod` |
| 1.1.0.x, 1.2.0.x | `php bin/console ibexa:reindex --env=prod` (the deprecated alias `ezplatform:reindex` still works and prints a warning) |
| 1.3.0.x | `php bin/console exponential:reindex --env=prod` |

Run a full reindex after installing, after switching engines, after a restore and after any import that wrote to the
database directly. The command of the 1.3.0.x line, verified in the reference installation, takes these options:

| Option | Meaning |
|---|---|
| `--iteration-count`, `-c` | objects per iteration (default `50`); lower it when memory is short |
| `--processes` | parallel child processes (default `auto`: CPU cores minus one; `1` or `0` disables) |
| `--no-purge` | do not empty the index first |
| `--no-commit` | do not commit after each iteration |
| `--since=<time>` | refresh changes since a time (`--since=yesterday`); implies `--no-purge` |
| `--content-ids=1,2,3` | refresh only these content items |
| `--subtree=<location id>` | refresh one subtree |
| `--content-type=<identifier>` | refresh one content type |

On a SQLite database, use `--processes=1`: parallel writers gain nothing on a single-file database
(chapter [7. Databases](07-databases.md)).

The shipped helpers and their names, as of 5 October 2026:

| Helper | `master`, `1.0.0.x` | `1.1.0.x` | `1.2.0.x` | `1.3.0.x` |
|---|---|---|---|---|
| `make reindex` | `ezplatform:reindex` | `ezplatform:reindex` (deprecated alias, prints a warning) | `ibexa:reindex` (since `v1.2.0.1`, commit `b81dbfcac`; the deprecated alias `ezplatform:reindex` before) | `exponential:reindex` (since `1.3.0.6`, commit `1635061cf`; `ibexa:reindex` before, which does not exist there) |
| Deployer task `solr:reindex` | `ezplatform:reindex` | `ibexa:reindex` | `ibexa:reindex` | `exponential:reindex` (same commit) |
| `exponential:install` runs | not applicable | `exponential:reindex` | `exponential:reindex` | `exponential:reindex` |

So a release checkout of 1.3.0.x (`1.3.0.5` and older) still has the broken `make reindex` and Deployer task; call
`php bin/console exponential:reindex` directly there, or take `Makefile` and `deploy/tasks/server.php` from
`1.3.0.6`.

The guides of the newer lines had two errors of their own up to `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5`: the
`doc/sevenx/INSTALL.md` of 1.1.0.x and 1.2.0.x showed `exponential:reindex --iteration-count=100` and
`--content-type=...`, options that the project's proxy command does not accept (it takes only `--siteaccess`; use
`ibexa:reindex` for them, which on 1.1.0.x has no content-type option at all); and the 1.3.0.x `INSTALL.md` said that
`ibexa:*` remains as an alias for migrated commands, which is not the case for `exponential:reindex` in the reference
installation. The guides of the releases of 5 October 2026 are corrected.

What a full reindex prints on 1.3.0.x (read from the command's code; the progress bar counts iterations of
`--iteration-count` items, not items):

```text
$ php bin/console exponential:reindex --env=prod --no-interaction
Re-indexing started for search engine: legacy

Purging index...
Re-creating index for <N> items across <N/50> iteration(s), using <P> parallel child processes:
 <progress bar with elapsed and estimated time and memory>

Finished re-indexing
```

With `--processes=0` or `1` the command first prints a warning about single-process mode (xdebug, the environment,
`memory_limit`, `--iteration-count`) and asks "Continue?"; answer it, or pass `--no-interaction` in scripts. "Could
not find any items to index, aborting." with exit status 1 means the command reached an empty repository: check
`DATABASE_URL` and the environment.

### 10.7.3 Solr

Solr installation, the schema and the core are described in the platform documentation linked in the references.
Two Netgen additions matter for operations:

- **Spellcheck suggestions** ("did you mean") work only with Solr and need changes to the Solr configuration,
  described in [`doc/netgen/SEARCH_SUGGESTIONS.md`](../netgen/SEARCH_SUGGESTIONS.md) and the Netgen Search Extra
  documentation.
- **Page indexing** of Netgen Search Extra has its own command on 1.3.0.x, `netgen-search-extra:index-pages`; it is
  only useful when the feature is configured.

Back up the Solr core like any other data, or plan to rebuild it with a full reindex; a reindex of the demo content
takes minutes, of a large site hours.

## 10.8 Images and image variations

Uploaded originals are stored under the repository's `var_dir`, `var/site`, below the document root:
`public/var/site/storage/images/` (`web/var/site/storage/images/` on 1.0.0.x). Variations, the resized copies the
templates ask for, are written on first request below `.../storage/images/_aliases/<variation>/`. Both are files on
disk and both belong in the backup of binary files ([10.10](#1010-backups-and-restore)); the variations can also be
regenerated.

The variations are defined in configuration (chapter [8](08-configuration.md)); LiipImagineBundle does the resizing,
with the `gd` driver in the reference installation's `config/packages/liip_imagine.yaml` (`imagick` is faster and
handles more formats when the extension is installed).

**Pre-generating variations.** The site bundle has a command that renders variations for every image ahead of time,
so the first visitor after an import or a restore does not wait:

```bash
php bin/console ngsite:content:generate-image-variations --env=prod \
    --variations=i30,i160,i320,i480,nglayouts_app_preview,ngcb_thumbnail
```

It also takes `--content-types`, `--fields` and `--subtrees` (comma-separated lists). The variation list above is the
one the `Makefile` target `images` uses.

**Removing variations.** After changing a variation's definition, remove its stored copies so they are rendered again:

```bash
php bin/console liip:imagine:cache:remove --filter=i320 --env=prod   # one variation
php bin/console liip:imagine:cache:remove --env=prod                 # all variations
```

On these lines the LiipImagine resolver is the repository's own (`IORepositoryResolver`); with no paths given it
purges the named variations from `_aliases` through the repository's variation purger, and leaves the originals
alone. Purge the HTTP cache afterwards so pages refer to the new files.

The 1.3.0.x kernel adds two maintenance commands for originals: `exponential:images:normalize-paths` and
`exponential:images:resize-original`. Both rewrite stored files; back up the storage directory before you run them,
and read their `--help` first.

## 10.9 Logs

| Line | Directory | Production handler |
|---|---|---|
| 1.0.0.x | `var/logs/` | `fingers_crossed` at `critical`, writing `%log_path%` (`var/logs/<env>.log` by default; `LOG_PATH` and `LOG_TYPE` override it) |
| 1.1.0.x to 1.3.0.x | `var/log/` | `fingers_crossed` at `error`, 404 and 405 excluded, buffer of 50 messages, JSON lines to `var/log/prod.log`; deprecations to `php://stderr` |

In `dev`, every line logs at `debug` level to `var/log/dev.log` and deprecations to `var/log/dev.deprecation.log`.
Those files grow without limit. The reference installation, which runs in `dev`, shows what that means: about 1 GB of
`dev.log` and 5.5 GB of `dev.deprecation.log`. Never run a public site in `dev`, and rotate the logs of every
environment.

The project ships two logrotate examples, [`doc/logrotate/ibexa`](../logrotate/ibexa) (1.1.0.x and later) and
[`doc/logrotate/ezplatform`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/logrotate/ezplatform) (1.0.0.x). Up to 5 October 2026 both rotated only `dev.log`,
so `prod.log`, `dev.deprecation.log` and the rest grew without limit; they now rotate every `*.log` in the log
directory (`var/logs/*.log` in the 1.0.0.x file):

```logrotate
/var/www/html/*/var/log/*.log {
    daily
    rotate 14
    maxsize 100M
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    su www-data www-data
}
```

Copy the file for your line to `/etc/logrotate.d/<site>`, then replace the path (`/var/www/html/*/` matches every
project below that directory) and the user and group with the ones that run PHP. Check it without rotating anything,
then once for real:

```bash
sudo logrotate -d /etc/logrotate.d/nexus      # debug: prints what it would do, changes nothing
sudo logrotate -f /etc/logrotate.d/nexus      # force one rotation now
ls -l var/log/                                # prod.log empty again, prod.log.1 next to it
```

What the directives are for, and what goes wrong without them:

- `su`: `var/log/` is writable by the web server's user, and logrotate refuses to rotate in such a directory as root
  ("because parent directory has insecure permissions"); `su` makes it act as that user.
- `copytruncate`: PHP-FPM workers and Exponential Velocity's persistent workers keep the log file open; renaming it
  would leave them writing into the renamed file. Copying and truncating keeps them writing into the right file, at
  the cost of a few lines written during the copy. If those lines matter, drop it and reload PHP-FPM or Velocity in a
  `postrotate` script.
- `maxsize`: rotates early on a day that writes a lot, which is what an error loop or a site left in `dev` does.

The legacy kernel's own logs (`ezpublish_legacy/var/log/*.log` and `ezpublish_legacy/var/<site>/log/*.log`, on
1.0.0.x to 1.2.0.x) are rotated by the legacy kernel itself when they reach their size limit; leave them out. On
1.1.0.x and later, deprecation messages in `prod` go to standard error, which ends up in the PHP-FPM log or in
Velocity's log; rotate those too.

Errors can also be sent to Sentry: every line requires `sentry/sentry-symfony` and reads `SENTRY_DSN`.

## 10.10 Backups and restore

A Nexus site is restored from four things. Back up all four together, at the same moment as far as possible:

| What | Where | Notes |
|---|---|---|
| The database | MySQL/MariaDB, PostgreSQL, or a SQLite file | content, users, layouts, rules, everything in the editor |
| Binary files | `public/var/site/storage/` (`web/var/site/storage/` on 1.0.0.x) | the originals are irreplaceable; `_aliases/` can be skipped and regenerated |
| Local configuration | `.env.local`, `.env.<env>.local`, `config/` changes not in Git, JWT key files if you configured any | contains secrets: store encrypted |
| The code | your Git repository and `composer.lock`, `yarn.lock` | rebuild `vendor/` and `node_modules/` from the lock files rather than backing them up |

On 1.0.0.x to 1.2.0.x, add `ezpublish_legacy/settings/override/` and `ezpublish_legacy/settings/siteaccess/` if
they are not in your repository, and `ezpublish_legacy/var/` only for its `storage/` subdirectory if legacy content
writes there.

**MySQL and MariaDB.** The site bundle has a dump command:

```bash
php bin/console ngsite:database:dump var/backup/nexus.sql --env=prod
```

It runs `mysqldump --opt --quick --single-transaction` with the configured connection, works only with MySQL and
MariaDB, and treats the file name as relative to the current directory: a leading `/` is stripped, so
`/srv/backup/x.sql` becomes `srv/backup/x.sql` below the project. For backups outside the project, run `mysqldump`
directly with the same options. Do not keep dumps below the document root.

**PostgreSQL.** `pg_dump -Fc` and `pg_restore`; see chapter [7](07-databases.md).

**SQLite.** The reference installation runs SQLite, with
`DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"`: the file name contains the
environment, so `dev` uses `var/data_dev.db` and `prod` would use a different, empty `var/data_prod.db`. Back up the
file the site actually uses, with SQLite's online backup rather than a plain copy of a file that is being written:

```bash
sqlite3 var/data_prod.db ".backup 'var/backup/data_prod.db'"
```

Chapter [7](07-databases.md) and the Exponential 6 book's
[database chapter](https://github.com/se7enxweb/exponential/blob/main/doc/install/09-databases.md) cover WAL files,
`VACUUM INTO` and restores.

**After a restore,** in this order: put the database and the storage back, run `cache:clear --env=prod`, clear the
persistence pool (`cache:pool:clear`), run a full reindex ([10.7.2](#1072-reindexing)), purge the HTTP cache
(`fos:httpcache:invalidate:tag ez-all`), and on the legacy lines clear the legacy caches. Then check the front page,
an image, a search and a login to the administration interface.

Netgen Layouts lives in the database and comes back with it. To move single layouts between installations, Layouts
has `nglayouts:export <type> <uuids>` and `nglayouts:import`; see their `--help`.

## 10.11 Performance

**Run in `prod` with debug off.** `APP_ENV=prod` and `APP_DEBUG=0` (1.1.0.x and later) or `SYMFONY_ENV=prod` and
`SYMFONY_DEBUG=0` (1.0.0.x). The `dev` environment rebuilds the container on changes, collects profiler data and
logs everything; it is many times slower and fills the disk ([10.9](#109-logs)). The site bundle has
`ngsite:profiler:clear-cache` for profiler data left by `dev`.

**OPcache.** Enable it for PHP-FPM and for Exponential Velocity, with room for the whole project:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.interned_strings_buffer=16
realpath_cache_size=4096K
realpath_cache_ttl=600
; production with deploys that always reload PHP:
opcache.validate_timestamps=0
```

With `validate_timestamps=0`, PHP never notices a changed file; every deploy must then reload PHP-FPM or Velocity
([10.12](#1012-deploying-a-change)). Under Velocity's persistent workers, `opcache.file_update_protection` (default
2 seconds) can keep files written just before a worker started out of the cache; reload after the cache warm-up has
finished, not during it.

**Preloading (1.1.0.x and later).** `config/preload.php` loads
`var/cache/prod/App_KernelProdContainer.preload.php` when it exists. Point `opcache.preload` at it, with
`opcache.preload_user` set to the web user, in the PHP-FPM pool or Velocity's PHP settings; it takes effect after the
`prod` cache has been warmed and PHP restarted. Preloading needs a restart after every deploy.

**The Composer autoloader.** The `Makefile` installs with `--no-dev -o` when `APP_ENV=prod`. For production, an
authoritative class map is faster still (`composer dump-autoload --no-dev --classmap-authoritative`); it is run as
part of a deploy, never by hand on a live tree, and it means a class not in the map is not found at all.

**The caches.** A Redis persistence pool for several servers ([10.3](#103-the-persistence-cache-pool)) and Varnish
in front ([10.4](#104-http-cache-and-purging)) carry most of the read load; on 1.1.0.x and later without Varnish,
every page is rendered by PHP.

**Image variations** pre-generated after imports ([10.8](#108-images-and-image-variations)).

## 10.12 Deploying a change

### 10.12.1 The order

Whatever tool runs it, a deploy of Nexus does the same steps in the same order. The commands are those of 1.3.0.x;
substitute the names from [10.1](#101-the-operators-map-per-line) for the other lines.

1. Update the code (`git pull`, or a new release directory).
2. Install PHP dependencies from the lock file: `composer install --no-dev -o` (described, not part of this book's
   tested steps; see chapter [3](03-getting-the-code.md)). Its `post-install-cmd` scripts run `cache:clear`,
   `assets:install` and `ngsite:symlink:project`, plus the legacy asset and autoload steps on the legacy lines.
3. Build the front end: `yarn install` and `yarn build:prod`, and the administration assets with
   `make ibexa-assets` on every line (chapter [9](09-frontend-and-themes.md)), which runs `composer ibexa-assets` on
   1.2.0.x and 1.3.0.x, `composer ezplatform-assets` on 1.0.0.x and the translation dump plus `yarn ez` on 1.1.0.x.
4. Run database migrations: `doctrine:migrations:migrate --allow-no-migration` and, where the project uses them, the
   Kaliop migrations (`kaliop:migration:migrate`).
5. Generate the GraphQL schema if you use GraphQL: `ibexa:graphql:generate-schema` (`ezplatform:` on 1.0.0.x).
6. Clear and warm the Symfony cache: `cache:clear --env=prod`, `cache:warmup --env=prod`, as the web user.
7. Reload PHP: Exponential Velocity (10.12.2) or PHP-FPM, so no process keeps old code.
8. Purge the HTTP cache if templates or output changed: `fos:httpcache:invalidate:tag ez-all`.
9. Restart Messenger workers if you run any: `messenger:stop-workers`.
10. Check: the front page, a content page with images, search, and the administration login.

### 10.12.2 With Exponential Velocity

Exponential Velocity, the recommended server (chapter [6](06-serving-the-site.md)), keeps PHP loaded in
persistent workers forked from a parent process. That is what makes it fast, and it is why step 7 is not optional:
until the workers are replaced, they run the code and the compiled container they started with. After the cache is
warmed:

```bash
qbixctl graceful            # = qbixconsole server:reload
```

`qbixctl` is installed in `/usr/sbin` by the Velocity packages; from a source tree it is `php sbin/qbixctl.php
graceful`, with the same options (`--config=<conf dir>/sites-enabled/<site>.conf` where more than one site is
configured). A reload closes the listening sockets, gives open connections up to five seconds, asks the workers to
stop, and starts a new pool from the new code and configuration. The control panel's Workers tab can also recycle the
workers without a full reload.

If Velocity's response cache is enabled for the site, also run `qbixconsole cache:clear`, which touches the cache's
generation marker so every stored page counts as stale ([10.4.6](#1046-velocitys-response-cache)).

Step 2 puts the bundle asset links back (`assets:install --symlink --relative` in the Composer scripts), and Velocity
answers 403 for links that leave the document root. On 1.3.0.x run `php bin/console assets:install public --env=prod`
after it, before the reload; on the lines with the legacy kernel, decide once whether the site uses copies or
`followSymlinks` ([chapter 14.11](14-security-hardening.md#1411-exponential-velocity)). A quick check after a deploy:

```bash
find public -maxdepth 2 -type l     # links Velocity will refuse if they lead out of public/
```

The Exponential 6 command `exp:velocity deploy` belongs to the Exponential 6 kernel and is not part of Nexus; on
Nexus the steps above are run with the Symfony console and `qbixctl`. These Velocity steps follow Velocity's own
documentation (`docs/workers.md`, `docs/console.md`, `docs/cache.md`); the Nexus reference installation on the
documentation server is served by Apache and PHP-FPM, so the sequence has not been exercised against it there.

### 10.12.3 With PHP-FPM

Reload the pool's master gracefully, so running requests finish:

```bash
systemctl reload php8.4-fpm     # the unit name depends on the distribution and PHP version
```

On Plesk, the unit is the one of the PHP version the domain uses, for example `plesk-php84-fpm`. Restart rather than
reload when `opcache.preload` is used.

### 10.12.4 With Deployer

Every line has a [`deploy.php`](../../deploy.php) and a `deploy/` directory for Deployer 6 (the project requires
`deployer/recipes` `^6.2`; the `dep` program itself is installed separately). The `deploy` task runs the steps above
in release directories: tests, upload of `.env.local`, `deploy:vendors`, front-end build and upload, GraphQL schema,
Sentry release, `deploy:cache:clear`, `deploy:cache:warmup`, `deploy:writable`, `database:kaliop:migrate`, the
`current` symlink, `server:symlink_public` and an OPcache reset through cachetool; after a successful deploy it runs
`httpcache:invalidate` (by default `fos:httpcache:invalidate:tag ez-all`, or a Varnish ban when
`http_cache_invalidate_method` is `varnish`).

Before using it, replace every example value in `deploy/hosts.php` and `deploy/parameters.php`: the host names, users,
paths, the repository URL (`git@bitbucket.org:netgen/example.git`), the Sentry organisation and tokens, and the PHP
path. `shared_dirs` contains `public/var/site/storage`, so the binary files survive releases, and `shared_files`
contains `.env.local`. Deployer only resets PHP-FPM's OPcache through cachetool; add a task that runs `qbixctl graceful` when
the site is served by Velocity, and one that runs `assets:install public` (copies) after `deploy:vendors` (chapter
[14.11](14-security-hardening.md#1411-exponential-velocity)). The reindex task names the right command on every
branch head and since `1.3.0.6`; on `1.3.0.5` and older it still calls `ibexa:reindex` ([10.7.2](#1072-reindexing)).

### 10.12.5 The Makefile

The [`Makefile`](../../Makefile) wraps the same commands; `make help` lists them. `APP_ENV` defaults to `dev`, so pass
it explicitly:

| Target | Runs |
|---|---|
| `vendor` | `composer install` (`--no-dev -o` with `APP_ENV=prod`) |
| `assets`, `assets-prod`, `assets-watch` | `yarn install` and `yarn build:dev`, `build:prod` or `watch`, after `nvm use` |
| `ibexa-assets` | the administration assets: `composer ezplatform-assets` (1.0.0.x), translation dump and `yarn ez` (1.1.0.x), `composer ibexa-assets` (1.2.0.x, 1.3.0.x) |
| `graphql-schema` | `ezplatform:graphql:generate-schema` (1.0.0.x, 1.1.0.x; 1.2.0.x up to `v1.2.0.0`), `ibexa:graphql:generate-schema` (1.2.0.x since `v1.2.0.1`, 1.3.0.x) |
| `clear-cache`, `clear-all-cache` | `cache:clear`; plus `cache:pool:clear $(CACHE_POOL)` |
| `images` | `ngsite:content:generate-image-variations` with the list in [10.8](#108-images-and-image-variations) |
| `migrations` | `doctrine:migration:migrate --allow-no-migration` |
| `reindex` | `ezplatform:reindex` (1.0.0.x, 1.1.0.x; 1.2.0.x up to `v1.2.0.0`), `ibexa:reindex` (1.2.0.x since `v1.2.0.1`), `exponential:reindex` (1.3.0.x) |
| `build` | `vendor`, `migrations`, `reindex`, assets, `ibexa-assets`, `graphql-schema`, `clear-cache` |
| `refresh` | `git pull --rebase` (stashing local changes), then `build` |

The branches fixed these targets on 5 October 2026, released the same day in `v2.5.0.7`, `1.0.0.11`, `v1.1.0.8`,
`v1.2.0.1` and `1.3.0.6`; a checkout of an earlier release still has the old ones:

| Target | Up to `v2.5.0.6`, `1.0.0.10`, `v1.1.0.7`, `v1.2.0.0`, `1.3.0.5` | Since the releases of 5 October 2026 |
|---|---|---|
| `clear-all-cache` | clears `cache.redis`, which does not exist with the default filesystem pool | clears `cache.global_clearer`, every pool ([10.3](#103-the-persistence-cache-pool)) |
| `reindex` (1.3.0.x) | `ibexa:reindex`, which does not exist there | `exponential:reindex` ([10.7.2](#1072-reindexing)) |
| `ibexa-assets` (1.0.0.x, 1.1.0.x) | calls `composer ibexa-assets`, a script these lines do not have, so `make build` stops there | runs the line's own administration build |
| every Node.js target | `nvm install $(cat .nvmrc)` expands to `nvm install` without a version | `$$(cat .nvmrc)`, and 1.0.0.x has an `.nvmrc` ([chapter 9.3](09-frontend-and-themes.md#93-installing-nodejs-and-yarn)) |

`build` also runs a full reindex on every call, which is slow on a large site. Treat `make refresh` as a development
convenience, not a production deploy. What `make build` runs, in order:

```bash
make build APP_ENV=prod
# vendor (composer install, --no-dev -o with APP_ENV=prod), migrations, reindex, assets-prod (assets in dev),
# ibexa-assets, graphql-schema, clear-cache; the first target that fails stops the run
```

## 10.13 Checklist

- [ ] The site runs with `APP_ENV=prod` and debug off (`SYMFONY_ENV=prod` on 1.0.0.x); nothing public runs in `dev`.
- [ ] Console commands are run as the web user, or ownership of `var/` is repaired afterwards.
- [ ] `CACHE_POOL` is chosen deliberately; with Redis, each installation has its own `CACHE_NAMESPACE`.
- [ ] The HTTP cache is understood: on 1.1.0.x and later there is no proxy unless Varnish (or a wrapped kernel) is
      added; `TRUSTED_PROXIES` lists every proxy.
- [ ] On 1.0.0.x, `app/AppCache.php` is the fixed class (14.7) and `app/config/http_cache.yml` lists your own
      administration host names.
- [ ] The crontab runs the platform's cron runner, the legacy cron parts on the legacy lines, and
      `ngscheduledvisibility:update` if scheduled visibility is enabled.
- [ ] Messenger workers run under a process manager only if something is routed to a queue.
- [ ] The reindex command matches the line (`ezplatform:`, `ibexa:` or `exponential:reindex`), and a full reindex was
      run after install, engine switch and restore.
- [ ] Logs under `var/log/` (`var/logs/` on 1.0.0.x) are rotated, with an adapted logrotate file.
- [ ] Database, `var/site/storage`, local configuration and secrets are backed up together, and a restore has been
      tested.
- [ ] OPcache is on; with `validate_timestamps=0`, every deploy reloads Velocity or PHP-FPM.
- [ ] Every deploy runs: dependencies, front end, migrations, cache clear and warm-up, PHP reload, HTTP purge, check.

## 10.14 References

In this book:

- [6. Serving the site](06-serving-the-site.md), [7. Databases](07-databases.md),
  [8. Configuration](08-configuration.md), [9. Front end and themes](09-frontend-and-themes.md),
  [3. Getting the code](03-getting-the-code.md), [Contents](README.md)

In this repository (master; the other lines have the same files unless noted):

- [`Makefile`](../../Makefile), [`deploy.php`](../../deploy.php), [`ngsite.cron`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/ngsite.cron) (1.0.0.x only)
- [`doc/logrotate/ibexa`](../logrotate/ibexa), [`doc/logrotate/ezplatform`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/logrotate/ezplatform)
- [`doc/varnish/varnish.md`](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/varnish/varnish.md) (1.0.0.x)
- [`doc/netgen/SEARCH_SUGGESTIONS.md`](../netgen/SEARCH_SUGGESTIONS.md)
- 1.3.0.x files:
  [`Makefile`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/Makefile),
  [`deploy/tasks/server.php`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/deploy/tasks/server.php),
  [`deploy/parameters.php`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/deploy/parameters.php),
  [`config/packages/ibexa.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/ibexa.yaml),
  [`config/packages/cache_pool/cache.redis.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/cache_pool/cache.redis.yaml),
  [`config/packages/monolog.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/monolog.yaml),
  [`config/packages/messenger.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/messenger.yaml),
  [`doc/sevenx/INSTALL.md`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/doc/sevenx/INSTALL.md)
- 1.2.0.x: [`config/packages/messenger.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.2.0.x/config/packages/messenger.yaml)

Exponential:

- The Exponential 6 book: <https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md>, its
  [operations handbook](https://github.com/se7enxweb/exponential/blob/main/doc/install/10-after-installing.md) and
  [database chapter](https://github.com/se7enxweb/exponential/blob/main/doc/install/09-databases.md)
- Exponential Velocity: <https://github.com/se7enxweb/exponential-velocity>, its
  [workers](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/workers.md),
  [console](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/console.md) and
  [response cache](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/cache.md) pages

Symfony and PHP:

- Symfony: [Cache](https://symfony.com/doc/current/cache.html),
  [Messenger](https://symfony.com/doc/current/messenger.html) and
  [Messenger in production](https://symfony.com/doc/current/messenger.html#deploying-to-production),
  [Deployment](https://symfony.com/doc/current/deployment.html),
  [Performance](https://symfony.com/doc/current/performance.html),
  [Logging](https://symfony.com/doc/current/logging.html)
- LiipImagineBundle: <https://symfony.com/bundles/LiipImagineBundle/current/index.html>
- PHP: [OPcache](https://www.php.net/manual/en/book.opcache.php),
  [OPcache settings](https://www.php.net/manual/en/opcache.configuration.php)
- Composer: [Autoloader optimization](https://getcomposer.org/doc/articles/autoloader-optimization.md)
- FOSHttpCacheBundle: <https://foshttpcachebundle.readthedocs.io/en/latest/>; FOSHttpCache:
  <https://foshttpcache.readthedocs.io/en/latest/>
- Deployer 6: <https://deployer.org/docs/6.x/getting-started>
- SQLite: [online backup](https://www.sqlite.org/backup.html), [command-line shell](https://www.sqlite.org/cli.html),
  [VACUUM INTO](https://www.sqlite.org/lang_vacuum.html#vacuuminto)

Platform documentation (upstream Ibexa; versioned documentation for
[2.5](https://doc.ibexa.co/en/2.5/), [3.3](https://doc.ibexa.co/en/3.3/) and [4.6](https://doc.ibexa.co/en/4.6/)):

- [Cache overview](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/cache/),
  [persistence cache](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/persistence_cache/),
  [HTTP cache](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/http_cache/http_cache/),
  [reverse proxy](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/http_cache/reverse_proxy/),
  [content-aware cache](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/http_cache/content_aware_cache/)
- [Solr search engine](https://doc.ibexa.co/en/latest/search/search_engines/solr_search_engine/install_solr/),
  [legacy search engine](https://doc.ibexa.co/en/latest/search/search_engines/legacy_search_engine/legacy_search_overview/),
  [reindexing](https://doc.ibexa.co/en/latest/search/reindex_search/)
- [Images and image variations](https://doc.ibexa.co/en/latest/content_management/images/images/)
- [Background tasks (Ibexa Messenger)](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/background_tasks/)
- [Performance](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/performance/),
  [backup](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/backup/),
  [clustering](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/clustering/clustering/)

Netgen:

- Netgen Layouts: [configuration reference](https://docs.netgen.io/projects/layouts/en/latest/reference/configuration.html)
- Netgen Site API: <https://docs.netgen.io/projects/site-api/en/latest/>
- Netgen Search Extra: <https://docs.netgen.io/projects/search-extra/en/latest/>,
  [spellcheck suggestions](https://docs.netgen.io/projects/search-extra/en/latest/reference/spellcheck_suggestions.html)

[Previous: 9. Front end and themes](09-frontend-and-themes.md) · [Next: 11. Upgrading between lines](11-upgrading-between-lines.md) ·
[Contents](README.md)
