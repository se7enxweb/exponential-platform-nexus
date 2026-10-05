# Installing and running Exponential Platform Nexus: the book

> This copy lives on the `1.3.0.x` branch (Platform v5, Symfony 7.4). The book covers every release line; the newest copy is on
> master: <https://github.com/se7enxweb/exponential-platform-nexus/blob/master/doc/book/README.md>

This book takes you from an empty server to a production Exponential Platform Nexus site, on any of its four lines,
and keeps it running afterwards. It covers choosing a line, the requirements, getting the code, installing the demo
site or a clean repository, Netgen Layouts and the media site design, serving the site with Exponential Velocity or a
classic web server, databases, configuration, the front-end build and day-to-day operations, and then upgrading
between lines, migrating existing sites into Nexus, troubleshooting and security hardening. Each chapter stands on its
own and ends with references to the files in this repository and to the official sources outside it.

In a hurry? [The short installation guide](../INSTALL.md) has the quick start per line and links back into the
chapters here.

## The four lines

| Line | Branch | Platform generation | Symfony | PHP | Legacy kernel |
|---|---|---|---|---|---|
| 1.0.0.x | `master` (`v2.5.0.x` tags) and `1.0.0.x` | eZ Platform 2.5 | 3.4 | 8.1 and newer in practice | yes |
| 1.1.0.x | `1.1.0.x` | Platform 3.3 | 5.4 | 8.0 and newer | yes |
| 1.2.0.x | `1.2.0.x` | Ibexa OSS 4.6 | 5.4 | 8.2 and newer | yes |
| 1.3.0.x | `1.3.0.x` | Platform v5 | 7.4 | 8.4 and newer | no |

[Chapter 1](01-introduction.md) explains the lines and how to choose one; [chapter 11](11-upgrading-between-lines.md#111-the-four-lines-side-by-side)
compares them package by package.

## Contents

### Part I: Before you install

| Chapter | What it covers |
|---|---|
| [1. Introduction: what you are installing](01-introduction.md) | What Nexus is and what is inside, the release lines, Nexus next to Exponential 6 and Exponential Platform Legacy, choosing a line, the parts of an installation, a glossary |
| [2. Requirements](02-requirements.md) | PHP and its extensions per line, databases, Node.js and Yarn, Composer, the web server, optional services, operating systems, sizing |
| [3. Getting the code](03-getting-the-code.md) | Branches, tags and Packagist versions, `composer create-project` and `git clone`, the packages each line pulls in, the Composer scripts, the lock file, a tour of the project root |

### Part II: Installing

| Chapter | What it covers |
|---|---|
| [4. Installing](04-installing.md) | Creating the database, then the installation of each line step by step, image variations, the first login, what can go wrong, running the install again |
| [5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md) | The media site demo content and designs, Netgen Layouts and its editor, the site bundle |

### Part III: Running the site

| Chapter | What it covers |
|---|---|
| [6. Serving the site](06-serving-the-site.md) | Exponential Velocity (recommended), Apache and nginx with PHP-FPM, Plesk, the HTTP cache with the Symfony proxy or Varnish, Docker, file permissions, HTTPS and proxies |
| [7. Databases](07-databases.md) | Which engine on which line, connection settings, the schema, migrations, MySQL and MariaDB, PostgreSQL, SQLite, backups, switching engines |
| [8. Configuration](08-configuration.md) | Where configuration lives, environments, `.env` files and secrets, siteaccesses, search and cache pools, languages, Site API, Layouts, site bundle parameters, image variations, the legacy bridge |
| [9. Front end and themes](09-frontend-and-themes.md) | The toolchain per line, building the site theme and the admin assets, publishing bundle assets, themes and the design engine, overriding templates, Layouts templates |
| [10. Operations](10-operations.md) | Caches, the persistence cache pool, HTTP cache purging, cron, Messenger workers, search, images, logs, backups, performance, deploying a change |

### Part IV: Keeping it running

| Chapter | What it covers |
|---|---|
| [11. Upgrading between lines](11-upgrading-between-lines.md) | 1.0.0.x to 1.1.0.x to 1.2.0.x to 1.3.0.x: package swaps, Symfony upgrades, the SQL per step, Netgen Layouts, Site API and Tags, configuration renames, the end of the legacy kernel, patch updates inside a line |
| [12. Migrating into Nexus](12-migrating-into.md) | From eZ Publish 4.x and 5.x, eZ Platform, Ibexa DXP and OSS, Exponential Platform Legacy and Exponential 6: which line to land on, the site bundle's structure, moving pages into Netgen Layouts, moving layouts between installations |
| [13. Troubleshooting](13-troubleshooting.md) | Symptom, cause and fix for Composer, the front-end build, the installer, the first requests, Netgen Layouts, caches, permissions, databases, the legacy kernel, signing in, search and images |
| [14. Security hardening](14-security-hardening.md) | What the web server must never serve, secrets, debug mode, the admin interfaces, trusted proxies in Symfony, the legacy kernel and Velocity, HTTP cache safety, sessions, headers, uploads, a go-live checklist |

## Which chapters do I need?

| Your situation | Read |
|---|---|
| A first test install on a laptop | [Short guide](../INSTALL.md), then chapters [3](03-getting-the-code.md) and [4](04-installing.md) |
| Choosing a line for a new project | Chapters [1](01-introduction.md), [2](02-requirements.md), then [11.1](11-upgrading-between-lines.md#111-the-four-lines-side-by-side) for the package differences |
| A production site on one server, served by Exponential Velocity | Chapters [2](02-requirements.md), [3](03-getting-the-code.md), [4](04-installing.md), [6](06-serving-the-site.md), [7](07-databases.md), [8](08-configuration.md), [10](10-operations.md), [14](14-security-hardening.md) |
| A production site behind Apache or nginx, with Varnish | As above; in chapter [6](06-serving-the-site.md) the Apache, nginx and HTTP cache sections, and [14.6](14-security-hardening.md#146-behind-a-proxy-trusted-proxies) and [14.7](14-security-hardening.md#147-http-cache-safety) |
| Building pages with Netgen Layouts and the demo designs | Chapters [5](05-the-demo-site-and-layouts.md), [8](08-configuration.md), [9](09-frontend-and-themes.md) |
| Moving an installed site to a newer line | Chapters [11](11-upgrading-between-lines.md), [13](13-troubleshooting.md) |
| Moving a site from eZ Publish, eZ Platform, Ibexa or Exponential into Nexus | Chapter [12](12-migrating-into.md) and the Exponential book chapters it names, then [11](11-upgrading-between-lines.md) |
| Going live | Chapter [14](14-security-hardening.md) and its checklist, then the checklists of chapters [6](06-serving-the-site.md) and [10](10-operations.md) |
| Something does not work | Chapter [13](13-troubleshooting.md), and the troubleshooting sections of chapters [4](04-installing.md) and [9](09-frontend-and-themes.md) |

## Conventions

- Commands run from the project root (`php bin/console ...`); commands for the legacy kernel go through the bridge
  (`php bin/console ezpublish:legacy:script ...`).
- "Line" means one of the four release lines above. Facts that differ per line say which line they apply to; files on
  another branch than the one you have checked out are read with `git show <branch>:<path>`.
- `example.com`, `USER`, `DATABASE` and similar are placeholders.
- Facts are dated: the chapters were checked against the branches and tags on 5 October 2026. That day the branches
  received fixes (the `dev` host switch and the cache header rewriting of the 2.5 generation, trusted proxies, the
  HTTP cache switch, `Makefile` targets), released the same day as `v2.5.0.7`, `1.0.0.11`, `v1.1.0.8`, `v1.2.0.1` and
  `1.3.0.6`. Where they matter, a chapter says what the current code does and what an installation made from an older
  tag still does; [chapter 14](14-security-hardening.md#which-code-do-i-run) shows how to tell which one you run, and
  [chapter 11.9](11-upgrading-between-lines.md#119-patch-updates-inside-a-line) how to take such a fix.
- Product names: Exponential Platform Nexus, Exponential (the legacy kernel, version 6), Exponential Platform Legacy,
  Exponential Velocity. Older product names appear only where they identify an upstream package or the system you
  migrate from.

## Other documentation

- [The short installation guide](../INSTALL.md)
- The per-line installation and operations notes on the newer branches: `doc/sevenx/INSTALL.md` on `1.1.0.x`,
  `1.2.0.x` and `1.3.0.x` (`git show 1.3.0.x:doc/sevenx/INSTALL.md`)
- The Netgen documents carried over from the upstream media site: [doc/netgen](../netgen/)
- Web server examples: [doc/apache2](../apache2/), [doc/nginx](../nginx/), [doc/varnish](https://github.com/se7enxweb/exponential-platform-nexus/tree/master/doc/varnish); log rotation:
  [doc/logrotate](../logrotate/)
- [The project README](../../README.md), [SECURITY.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/SECURITY.md), [UPGRADE.md](https://github.com/se7enxweb/exponential-platform-nexus/blob/master/UPGRADE.md)
- The Exponential 6 book, for the legacy kernel and for migrations:
  [doc/install in se7enxweb/exponential](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md)
