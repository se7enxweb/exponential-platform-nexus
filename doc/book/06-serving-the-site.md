# 6. Serving the site: Velocity, Apache, nginx, Varnish and Docker

An installed Exponential Platform Nexus is a Symfony application: a directory of PHP code, configuration and built
assets, with one public directory that holds the front controller and the files a browser may fetch. Something has to
accept connections, speak HTTP and HTTPS, hand those files out and pass every other request to the front controller.
This chapter shows how, for all four lines. It starts with **Exponential Velocity**, the application server that ships
for Exponential and is the recommended way to serve a site at every stage: Velocity listens on the HTTP and HTTPS
ports itself, keeps PHP loaded in persistent workers and manages its own certificates, so no separate web server and
no separate HTTPS tool is needed. The chapter then covers the traditional setups that the repository ships examples
for (Apache with PHP-FPM, nginx with PHP-FPM, Plesk), the HTTP cache layer (the Symfony proxy, Varnish and
FOSHttpCache), the Docker files, file permissions, and HTTPS behind a proxy.

[Previous: 5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md) · [Next: 7. Databases](07-databases.md) · [Contents](README.md)

---

## Contents of this chapter

1. [The choice in one table](#61-the-choice-in-one-table)
2. [How a request reaches the application, per line](#62-how-a-request-reaches-the-application-per-line)
3. [Exponential Velocity, the recommended way](#63-exponential-velocity-the-recommended-way)
   1. [Get the engine](#631-get-the-engine)
   2. [Serve public/index.php: the 1.1.0.x, 1.2.0.x and 1.3.0.x lines](#632-serve-publicindexphp-the-110x-120x-and-130x-lines)
   3. [A site file: entry points, static files and headers](#633-a-site-file-entry-points-static-files-and-headers)
   4. [Serve web/app.php: the 1.0.0.x line](#634-serve-webappphp-the-100x-line)
   5. [The environment: APP_ENV and .env.local](#635-the-environment-app_env-and-envlocal)
   6. [HTTPS served by Velocity itself](#636-https-served-by-velocity-itself)
   7. [Ports, user, group and workers](#637-ports-user-group-and-workers)
   8. [Velocity's response cache and a Symfony site](#638-velocitys-response-cache-and-a-symfony-site)
   9. [Running Velocity as a service](#639-running-velocity-as-a-service)
   10. [What has been verified, and what has not](#6310-what-has-been-verified-and-what-has-not)
4. [Apache with PHP-FPM](#64-apache-with-php-fpm)
5. [nginx with PHP-FPM](#65-nginx-with-php-fpm)
6. [Plesk: the reference installation's virtual host](#66-plesk-the-reference-installations-virtual-host)
7. [The HTTP cache: Symfony proxy, Varnish and FOSHttpCache](#67-the-http-cache-symfony-proxy-varnish-and-foshttpcache)
8. [Docker](#68-docker)
9. [File permissions and ownership](#69-file-permissions-and-ownership)
10. [HTTPS, reverse proxies and trusted proxies](#610-https-reverse-proxies-and-trusted-proxies)
11. [Known inaccuracies in the older server documents](#611-known-inaccuracies-in-the-older-server-documents)
12. [Checklist](#612-checklist)
13. [References](#613-references)

Every command in this chapter is run from the project root, the directory that holds `composer.json`, `bin/console`
and the public directory. Paths such as `/var/www/nexus` stand for your own project root.

---

## 6.1 The choice in one table

| | Exponential Velocity | Apache + PHP-FPM | nginx + PHP-FPM | Varnish |
|---|---|---|---|---|
| Role | **recommended** for every stage | traditional | traditional | an HTTP cache in front of one of the others |
| Separate web server needed | **no** | yes | yes | yes, Varnish only caches |
| Separate HTTPS / certificate tool needed | **no**: own certificates, self-signed fallback, Let's Encrypt | yes (`mod_ssl` and certbot or a hosting panel) | yes | Varnish does not speak TLS; terminate it in front |
| Where it comes from | `se7enxweb/exponential-velocity` (Packagist, GitHub, or the standalone binary) | the operating system | the operating system | the operating system |
| PHP kept loaded between requests | yes (persistent workers) | no | no | n/a |
| Shipped example in this repository | this chapter | [`doc/apache2/`](../apache2/) | [`doc/nginx/`](../nginx/) | [`doc/varnish/`](../varnish/) (master and 1.0.0.x only) |
| Tag-based purge on publish (FOSHttpCache) | no | n/a | n/a | yes, with the `xkey` module |

Use a traditional server when your hosting does not allow long-running processes, when a hosting panel such as Plesk
owns the web server (section 6.6), or when you already run a tuned Apache or nginx you want to keep. Put Varnish in
front of any of them when you want shared HTTP caching with purge on publish (section 6.7).

## 6.2 How a request reaches the application, per line

The four lines do not share one layout. The document root, the front controller and the names of the environment
variables differ, and every server configuration in this chapter has to match the line it serves.

| Line | Upstream base | Document root | Front controller | Environment variables read by the front controller |
|---|---|---|---|---|
| 1.0.0.x (and `master`) | eZ Platform 2.5, Symfony 3.4 | `web/` | `web/app.php` (Symfony `AppKernel`, optional `AppCache`) | `SYMFONY_ENV`, `SYMFONY_DEBUG`, `SYMFONY_HTTP_CACHE`, `SYMFONY_TRUSTED_PROXIES` |
| 1.1.0.x | eZ Platform 3.3, Symfony 5.4 | `public/` | `public/index.php` (Symfony Runtime) | `APP_ENV`, `APP_DEBUG` (through `.env`) |
| 1.2.0.x | Ibexa OSS 4.6, Symfony 5.4 | `public/` | `public/index.php` (Symfony Runtime) | `APP_ENV`, `APP_DEBUG` |
| 1.3.0.x | Ibexa v5, Symfony 7.4 | `public/` | `public/index.php` (Symfony Runtime), plus `public/index_rest.php` and `public/index_cluster.php` | `APP_ENV`, `APP_DEBUG` |

What the files say:

- On **1.1.0.x, 1.2.0.x and 1.3.0.x** `public/index.php` is the six-line Symfony Runtime front controller: it requires
  `vendor/autoload_runtime.php` and returns a closure that builds `App\Kernel` from `APP_ENV` and `APP_DEBUG`.
  Everything else is routed inside Symfony, so the server only has to send every request that is not a file to
  `index.php`.
- On **1.0.0.x** the front controller is `web/app.php`. `web/index.php` exists but is an **empty file**, so a server
  that falls back to `index.php` by default serves blank pages on this line; it must be told to use `app.php`
  (sections 6.3.4 and 6.4). `app.php` reads `SYMFONY_ENV` (default `prod`), `SYMFONY_DEBUG`, `SYMFONY_HTTP_CACHE`
  (wraps the kernel in `AppCache`, the Symfony reverse proxy, unless the environment is `dev`) and
  `SYMFONY_TRUSTED_PROXIES`, loads an optional `.env.php` from the project root, and answers `400 Bad Request` when the
  front controller's own name appears in the URL. It also switches to the `dev` environment for any host name that
  contains `dev.`; keep that in mind when you name a production host.
- The `master` branch carries the 1.0.0.x layout (`web/app.php`, `app/`, `ezpublish_legacy`) with a few files of the
  later layout beside it; serve it as 1.0.0.x.
- On **1.3.0.x** `public/index_rest.php` passes a request to the legacy `index_rest.php` when an `ezpublish_legacy/`
  directory is installed next to the project, and otherwise hands it to `index.php` (the REST API of the v5 kernel is
  served under `/api/ibexa/v2` by `index.php`). `public/index_cluster.php` always changes into `../ezpublish_legacy/`
  and has no fallback, so do not expose it unless the legacy cluster setup is installed.

Binary files (images and other uploads) live below `<document root>/var/site/storage/` on every line: the shipped
`var_dir` setting is `var/site` in `app/config/ezplatform.yml` (1.0.0.x) and `config/packages/ibexa.yaml` (later
lines). Built front-end assets are in `public/assets/` (Encore, see [chapter 9](09-frontend-and-themes.md)), bundle
assets in `public/bundles/` (or `web/bundles/` and `web/assets/` on 1.0.0.x).

## 6.3 Exponential Velocity, the recommended way

Exponential Velocity is a web and application server written in PHP. It accepts connections itself, serves static
files directly, and runs PHP in a pool of persistent workers, so a request does not pay for starting PHP, loading the
autoloader and booting the container from nothing. It reads `.htaccess` rules, routes clean URLs to a front
controller the way `try_files $uri /index.php` does in nginx, and has framework presets: `--preset=symfony` is the
one for Exponential Platform Nexus. For the full engine documentation see
[the Velocity repository](https://github.com/se7enxweb/exponential-velocity) and, for how Exponential (the legacy
line) uses it, chapter 8 of the [Exponential 6 book](https://github.com/se7enxweb/exponential/blob/main/doc/install/08-serving-the-site.md#83-exponential-velocity-the-recommended-way).

### 6.3.1 Get the engine

Velocity is the Composer package `se7enxweb/exponential-velocity` (MIT licence). Its programs are:

| Program | Path in the package | What it does |
|---|---|---|
| the server | `sbin/qbixserver.php` (also `sbin/qbixserver.phar`) | serves the site |
| control, the `apachectl` way | `sbin/qbixctl.php` | `start`, `stop`, `restart`, `graceful`, `status`, `-t`, `ensite`, `dissite`, ... |
| the console | `sbin/qbixconsole.php` | `qbixconsole list` shows every command, for example `cache:clear` and `ssl:show` |

Keep the engine outside the project, so that a `composer install` of the site never touches it:

```bash
git clone https://github.com/se7enxweb/exponential-velocity.git /opt/exponential-velocity
php /opt/exponential-velocity/sbin/qbixserver.php --version
```

Operating-system packages install the same programs as `/usr/sbin/qbixserver`, `/usr/sbin/qbixctl` and
`/usr/sbin/qbixconsole` (with links in `/usr/bin`), and the release page carries a standalone binary; see
[docs/running.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/running.md) in the engine.

### 6.3.2 Serve public/index.php: the 1.1.0.x, 1.2.0.x and 1.3.0.x lines

From the project root:

```bash
php /opt/exponential-velocity/sbin/qbixserver.php --root=public --preset=symfony --port=8080
```

What each part does, as the engine's code has it:

- `--root=public` makes `public/` the document root. A file that exists below it (`/assets/...`, `/bundles/...`,
  `/var/site/storage/images/...`) is sent as it is; any other path goes to the front controller.
- The front controller is `index.php` in the root unless the configuration names another
  (`Q.webserver.frontControllers`, section 6.3.3). `public/index.php` is exactly what the Symfony lines need.
- `--preset=symfony` sets the front-controller rewrite to `index.php` and these PHP limits for the workers:
  `upload_max_filesize=10M`, `post_max_size=12M`, `memory_limit=256M`, `max_execution_time=60`,
  `session.gc_maxlifetime=1440`. Raise them in the site file if editors upload larger files (section 6.3.3).
- `--port=8080` keeps the first start away from port 80. Without `--port` the server listens on 80, and on 443 too
  when it has a certificate.

The server rewrites `header()`, `setcookie()`, `session_start()`, `exit` and about forty other functions at include
time so that they act on the current response and end the request rather than the worker; that is what lets the
Symfony Runtime's `autoload_runtime.php`, which ends with `exit`, run in a persistent worker. The list is in the
engine's [docs/reset.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/reset.md).

Check the configuration without starting anything:

```bash
php /opt/exponential-velocity/sbin/qbixserver.php --root=public --preset=symfony -t
```

### 6.3.3 A site file: entry points, static files and headers

Options on the command line are enough for a first start. For a real site, write the settings into a JSON site file,
in the Debian-style tree that Velocity uses (`/etc/vc/sites-available/<site>.conf`, enabled with
`qbixctl ensite <site>`; `/etc/qbix` is read as well), and start the server with `--config=<file>`. The keys below
are all documented in the engine's
[docs/configuration.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/configuration.md) and
[docs/https.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/https.md); the static-file pattern
mirrors the rules of the shipped Apache example (section 6.4) plus the Encore output directory.

```json
{
    "Q": {
        "compat": {
            "preset": "symfony",
            "ini": { "upload_max_filesize": "48M", "post_max_size": "50M" }
        },
        "web": {
            "static": {
                "paths": [
                    "^/(assets/|bundles/|images/|build/|favicon\\.(ico|png)$|robots\\.txt$|var/([^/]+/)?storage/images(-versioned)?/|var/([^/]+/)?storage/original/image/.+\\.svg$)"
                ]
            },
            "https": {
                "port": 443,
                "mode": "letsencrypt",
                "acme": { "email": "hostmaster@example.com", "domains": ["www.example.com"] }
            }
        },
        "webserver": {
            "scripts": ["/index.php"],
            "user": "www-data",
            "group": "www-data",
            "headers": {
                "X-Content-Type-Options": "nosniff",
                "X-Frame-Options": "SAMEORIGIN",
                "Referrer-Policy": "strict-origin-when-cross-origin"
            }
        }
    }
}
```

- `webserver.scripts` lists the scripts that may run when asked for by name. Any other `.php` path goes to the front
  controller, as it would behind the `RewriteRule .* /index.php` of the Apache example. On 1.3.0.x with the legacy
  bridge installed, add `"/index_rest.php"` and route `^/api/` to it with
  `"frontControllers": { "^/api/": "index_rest.php" }`, which is what the reference installation's Apache rules do
  (section 6.6).
- `web.static.paths` restricts which files are sent as files. It is optional for these lines, because `public/` holds
  only public files, but it keeps a stray file from being served. Anchor every pattern with `^/`.
- `compat.ini` overrides the preset's PHP limits (the `ini` key of the preset, written into `Q.compat`).
- A pooled worker does not read `.htaccess` itself; rewrites to a script other than `index.php` have to be written as
  `frontControllers` (engine documentation, "Only the entry points run").

```bash
sudo cp nexus.conf /etc/vc/sites-available/nexus.conf
sudo php /opt/exponential-velocity/sbin/qbixctl.php ensite nexus
sudo php /opt/exponential-velocity/sbin/qbixserver.php --config=/etc/vc/sites-enabled/nexus.conf --root=/var/www/nexus/public
```

### 6.3.4 Serve web/app.php: the 1.0.0.x line

On 1.0.0.x (and `master`) the document root is `web/` and the front controller is `app.php`. Because `web/index.php`
is an empty file, the default fallback to `index.php` must be replaced. Route every path that is not a file to
`app.php` and allow only that script to run:

```json
{
    "Q": {
        "compat": { "preset": "symfony" },
        "webserver": {
            "scripts": ["/app.php"],
            "frontControllers": { "^/": "app.php" }
        }
    }
}
```

```bash
php /opt/exponential-velocity/sbin/qbixserver.php --root=web --config=/etc/vc/sites-enabled/nexus-1.0.conf --port=8080
```

The front-controller patterns are checked in order and the first one whose script exists inside the root wins, so
`^/` sends every clean URL to `app.php`; files that exist below `web/` are still sent as files. Set `SYMFONY_ENV`
and the other variables of section 6.2 in the service's environment (section 6.3.9) or in `.env.php`, which
`app.php` loads.

`web/.htaccess` on this line is a link to a development `.htaccess` that also asks for HTTP Basic authentication
against a password file of the original authors' servers. Do not rely on it; write the routing into the site file as
above.

### 6.3.5 The environment: APP_ENV and .env.local

The shipped `.env` of 1.1.0.x, 1.2.0.x and 1.3.0.x sets `APP_ENV=dev`. A production site sets `APP_ENV=prod` and
`APP_DEBUG=0` in `.env.local` (never committed) or in the server's environment; the Symfony Runtime reads `.env`,
`.env.local`, `.env.$APP_ENV` and `.env.$APP_ENV.local` in that order. Because Velocity's workers are long-running,
**restart Velocity after changing `.env.local`, after `cache:clear` in `prod`, and after a deploy**, so that no worker
keeps the old container:

```bash
php bin/console cache:clear --env=prod
sudo php /opt/exponential-velocity/sbin/qbixctl.php graceful
```

The database, mail, search and cache variables in `.env` are covered in [chapter 7](07-databases.md) and
[chapter 8](08-configuration.md); deploy routines in [chapter 10](10-operations.md).

### 6.3.6 HTTPS served by Velocity itself

All HTTPS settings live under `Q.web.https` in the site file. The source of the certificate is `mode`:

| `mode` | Use |
|---|---|
| `letsencrypt` (same as `acme`) | Velocity obtains and renews the certificate itself; port 80 must be reachable and DNS must point at the server. Set `acme.email` and `acme.domains`. |
| `files` (same as `manual`) | your own certificate and key, from any CA or a hosting panel: `cert` and `key` paths. |
| `archive`, `pkcs12`, `certbot`, `remote` | other sources, see the engine's `docs/https.md`. |
| `self-signed` | development; the server makes its own certificate. |

When no source yields a usable certificate, Velocity serves HTTPS on a self-signed certificate rather than switching
HTTPS off. It renews and swaps certificates without a restart. `qbixconsole ssl:show` reports the state. The worker
sees `$_SERVER['HTTPS'] = 'on'` for a TLS request, so Symfony generates `https://` URLs without any trusted-proxy
setting.

### 6.3.7 Ports, user, group and workers

The options below are the ones `qbixserver.php --help` prints:

| Option | Meaning |
|---|---|
| `--host=IP` | bind address (default `0.0.0.0`) |
| `--port=PORT` | HTTP port (default 80) |
| `--https-port=PORT` | HTTPS port (default 443, when certificates are available) |
| `--socket=PATH` | listen on a Unix socket instead, for a proxy in front |
| `--user=NAME`, `--group=NAME` | the user and group the workers run as when the server is started as root (default: `Q.webserver.user`, then the owner of the document root; never root unless `--allow-root-workers`) |
| `--workers=N` | persistent workers (default: what fits in memory, at most 8 per core and 64 in all, never fewer than 4) |
| `--config=FILE` | the JSON site file |
| `--preset=NAME` | `laravel`, `symfony`, `wordpress`, `drupal`, `exponential` |
| `--pid=PATH` | PID file, used by `--stop` and `--reload` |
| `-t` | test the configuration and exit |

Run the workers as the same user that runs `bin/console` (cron jobs, Messenger workers, deploys), or as a user in the
same group, so that files written to `var/` by one are writable by the other (section 6.9).

Long-running console work (migrations, imports, reindexing) belongs on the command line, not in a web request.

### 6.3.8 Velocity's response cache and a Symfony site

Velocity can keep rendered pages and answer repeat requests without running PHP. It is **off** unless
`Q.web.cache.enabled` is `true`, and it decides what to keep from `Cache-Control` alone; it does not understand the
cache tags with which the Ibexa HTTP cache layer purges a page on publish (section 6.7). For Exponential Platform
Nexus:

- **Leave it off** when Varnish or the Symfony proxy does the HTTP caching, or when editors expect a change to show up
  at once.
- If you turn it on, add the platform's session cookies to `skip.cookies`, because its default list
  (`Q_sid`, `PHPSESSID`) does not include them. The session cookie is named `IBX_SESSION_ID{siteaccess_hash}` on the
  v5 line (verified in the kernel's `default_settings.yml`); the older lines use the upstream `eZSESSID` prefix. The
  names are matched by prefix:

  ```json
  { "Q": { "web": { "cache": {
      "enabled": true,
      "dir": "/var/cache/vc/nexus",
      "skip": { "cookies": ["PHPSESSID", "eZSESSID", "IBX_SESSION_ID"] }
  } } } }
  ```

  and clear it after every publish wave or deploy with `qbixconsole cache:clear` (which touches the generation
  marker). Content published from the back office is **not** purged from this cache by the platform.

### 6.3.9 Running Velocity as a service

The engine ships a systemd unit, `packaging/systemd/exponential-velocity.service`, which starts

```
/usr/sbin/qbixserver --config=${QBIX_SITE} --root=${QBIX_ROOT} --pid=/run/exponential-velocity/qbixserver.pid $QBIX_OPTS
```

with the variables from `/etc/default/exponential-velocity`, reloads with `--reload`, and gives the process
`CAP_NET_BIND_SERVICE` so it can bind ports 80 and 443 without running as root. For this project set, in that file:

```bash
QBIX_ROOT=/var/www/nexus/public          # /var/www/nexus/web on 1.0.0.x
QBIX_SITE=/etc/vc/sites-enabled/nexus.conf
QBIX_OPTS=--preset=symfony
```

The shipped unit runs as the user `qbix`; change `User=` and `Group=` (in a drop-in, `systemctl edit
exponential-velocity`) to the user that owns `var/`, and note that the unit sets `ProtectHome=true`, so a project
below `/home` is not visible to it. Environment variables such as `APP_ENV=prod` can go into the same
`/etc/default/exponential-velocity` file.

### 6.3.10 What has been verified, and what has not

- Verified from the engine's code and documentation: the options, the `symfony` preset and its values, the
  front-controller and `scripts` rules, the HTTPS modes, the response cache rules, the systemd unit.
- Verified from the project's branches: the front controllers, document roots and variables of section 6.2.
- **Not verified on a running server:** no Exponential Platform Nexus line was served by Velocity on the machine
  where this chapter was written (the v5 reference installation is served by Plesk's Apache and PHP-FPM, section 6.6).
  Test a new site on a high port with `-t` and then a first start before moving production traffic to it.

## 6.4 Apache with PHP-FPM

The repository ships Apache 2.4 examples in [`doc/apache2/`](../apache2/):

| File | For | Document root in the example |
|---|---|---|
| [`media-site-vhost.conf`](../apache2/media-site-vhost.conf) | 1.1.0.x, 1.2.0.x, 1.3.0.x (identical on all three branches) | `/var/www/media-site/public`, `index.php`, `APP_*` variables |
| [`netgen-site-vhost.conf`](../apache2/netgen-site-vhost.conf), [`vhost.template`](../apache2/vhost.template) | 1.0.0.x and `master` | `.../web`, `app.php`, `SYMFONY_*` variables |
| [`Readme.md`](../apache2/Readme.md) | Apache modules and MPM | |

Required modules are `mod_rewrite` and `mod_env`; `mod_setenvif` and `mod_expires` are recommended; with PHP-FPM use
the `event` MPM and `mod_proxy_fcgi`. A virtual host for 1.1.0.x to 1.3.0.x, condensed from `media-site-vhost.conf`
(keep the full rule list from the file):

```apache
<VirtualHost *:443>
    ServerName www.example.com
    DocumentRoot /var/www/nexus/public

    SSLEngine on
    SSLCertificateFile    /etc/ssl/example/fullchain.pem
    SSLCertificateKeyFile /etc/ssl/example/privkey.pem

    <Directory /var/www/nexus/public>
        Options +FollowSymLinks -Indexes
        AllowOverride None
        Require all granted
    </Directory>

    SetEnvIf Request_URI ".*" APP_ENV=prod
    SetEnv APP_DEBUG "0"

    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php-fpm/nexus.sock|fcgi://localhost/"
    </FilesMatch>

    DirectoryIndex index.php
    RewriteEngine On
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{ENV:REDIRECT_STATUS} ^$
    RewriteRule ^/index\.php(/(.*)|$) /$2 [R=301,L]
    RewriteRule ^/\.well-known/acme-challenge/ - [L]
    RewriteRule ^/var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ - [F]
    RewriteRule ^/var/([^/]+/)?storage/images(-versioned)?/.* - [L]
    RewriteRule ^/var/([^/]+/)?storage/original/image/(.*)\.svg - [L]
    RewriteRule ^/favicon\.ico - [L]
    RewriteRule ^/images/ - [L]
    RewriteRule ^/bundles/ - [L]
    RewriteRule ^/assets/ - [L]
    RewriteRule ^/([^/]+/)?index\.php([/?#]|$) - [R=404,L]
    RewriteRule .* /index.php
</VirtualHost>
```

Notes:

- The `HTTP_AUTHORIZATION` line is needed under PHP-FPM for HTTP Basic authentication of the REST API.
- The rules sit in the virtual host, with `AllowOverride None`, which is faster and safer than `.htaccess`. On
  1.3.0.x `public/.htaccess` is listed in `.gitignore`; none is shipped.
- The `APP_HTTP_CACHE` and `TRUSTED_PROXIES` lines that the example carries as comments are discussed in sections
  6.7 and 6.10; on the Symfony Runtime lines they are not read by the shipped front controller.
- For **1.0.0.x** use `netgen-site-vhost.conf`: document root `web/`, `DirectoryIndex app.php`, the final rule
  `RewriteRule .* /app.php`, and the variables `SYMFONY_ENV`, `SYMFONY_DEBUG`, `SYMFONY_HTTP_CACHE`,
  `SYMFONY_TRUSTED_PROXIES`. It also carries the legacy-bridge rules for `ezpublish_legacy` design and extension
  files.

Apply with `apachectl configtest` and a graceful reload of Apache and of the PHP-FPM pool that serves the site.

## 6.5 nginx with PHP-FPM

[`doc/nginx/`](../nginx/) holds two server blocks and two sets of included rules:

| File | For |
|---|---|
| [`media-site.conf`](../nginx/media-site.conf) with [`ibexa_params.d/`](../nginx/ibexa_params.d/) | 1.1.0.x, 1.2.0.x, 1.3.0.x (identical on all three branches) |
| [`netgen-site.conf`](../nginx/netgen-site.conf) with [`ez_params.d/`](../nginx/ez_params.d/), [`vhost.template`](../nginx/vhost.template) | 1.0.0.x and `master` (`root .../web`, `app.php`, legacy rules) |
| [`Readme.md`](../nginx/Readme.md) | notes |

Copy the `ibexa_params.d` directory next to your nginx configuration (for example `/etc/nginx/ibexa_params.d/`) and
adapt `media-site.conf`:

```nginx
server {
    listen 443 ssl;
    http2 on;
    server_name www.example.com;
    root /var/www/nexus/public;

    ssl_certificate     /etc/ssl/example/fullchain.pem;
    ssl_certificate_key /etc/ssl/example/privkey.pem;

    include ibexa_params.d/ibexa_rewrite_image_params;
    include ibexa_params.d/ibexa_rewrite_params;

    client_max_body_size 48m;
    fastcgi_read_timeout 90s;

    location / {
        location ~ ^/index\.php(/|$) {
            include ibexa_params.d/ibexa_fastcgi_params;
            fastcgi_pass unix:/run/php-fpm/nexus.sock;
            fastcgi_param APP_ENV prod;
            fastcgi_param APP_DEBUG 0;
        }
        location ~ ^/var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ {
            return 403;
        }
    }

    include ibexa_params.d/ibexa_server_params;
}
```

Two adjustments to the shipped file are needed: its `fastcgi_pass` points at a PHP 7.3 socket
(`/var/run/php/php7.3-fpm.sock`), which no line of this project supports any more, and it sets `APP_ENV dev`. Use
the socket of your PHP 8 pool and `prod`. `ibexa_rewrite_params` sends every request that is not an image, the
favicon, `/bundles/` or `/assets/` to `/index.php`; unlike the Apache example it has no rule for `/images/`, so add
`rewrite "^/images/(.*)" "/images/$1" break;` if the site serves files from `public/images/`. The `http2 on;`
directive needs nginx 1.25.1 or later; older versions write `listen 443 ssl http2;`.

Test with `nginx -t` and reload.

## 6.6 Plesk: the reference installation's virtual host

The v5 reference installation (1.3.0.x) runs under Plesk: nginx on ports 80 and 443 proxies to Apache, which hands
PHP to a per-site PHP-FPM pool (PHP 8.4 for that site). Plesk generates the main configuration; the site's own
additions go into **Apache & nginx Settings** of the domain, which Plesk writes to `conf/vhost_ssl.conf` (HTTPS),
`conf/vhost.conf` (HTTP) and `conf/vhost_nginx.conf` in the domain's system directory. Set the domain's document root
to the project's `public/` directory.

The reference installation's additional HTTPS directives, with the site's own authentication and paths left out:

```apache
<Directory /var/www/vhosts/example.com/nexus/public>
    AllowOverride None
    DirectoryIndex index.php
    Options +FollowSymLinks -ExecCGI
    SSLRequireSSL

    <IfModule mod_rewrite.c>
        RewriteEngine On
        RewriteBase /
        RewriteRule ^api/ index_rest.php [L]
        RewriteRule ^index_rest\.php - [L]
        RewriteRule ^var/([^/]+/)?storage/images(-versioned)?/.* - [L]
        RewriteRule ^favicon\.ico - [L]
        RewriteRule ^robots\.txt - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.php [L]
    </IfModule>
</Directory>

# Reuse connections between Apache and this site's PHP-FPM pool.
<IfModule mod_proxy_fcgi.c>
    <Proxy "fcgi://nexus-example-com/">
        ProxySet enablereuse=On
    </Proxy>
    <Files ~ (\.php$)>
        SetHandler "proxy:unix:/var/www/vhosts/system/example.com/php-fpm.sock|fcgi://nexus-example-com/"
    </Files>
</IfModule>
```

What to know about it:

- The live file also carries rules for `design/`, `extension/`, `share/icons/`, `packages/` and
  `index_treemenu.php` that belong to the Exponential legacy layout. They are harmless on 1.3.0.x but do nothing
  useful there, and `public/index_treemenu.php` does not exist in that installation; leave them out of a new site.
- The `fcgi://` name in `<Proxy>` is an arbitrary label; the real socket is the `unix:` path in `SetHandler`, which
  Plesk creates per domain.
- The reference installation protects the whole host with HTTP Basic authentication because it is a staging site.
  A public site does not.
- `vhost_nginx.conf` there adds `expires 1y` for static file types; with `public/assets/` versioned by Encore that is
  safe, for `var/site/storage/images/` it is safe because variation file names change when the image changes.
- After changing PHP settings or classes, reload the site's PHP-FPM master (the Plesk service for that PHP version),
  not the operating system's `php-fpm`.

The reference installation runs with `APP_ENV=dev` in `.env` because it is a development reference; a production site
sets `prod` (section 6.3.5).

## 6.7 The HTTP cache: Symfony proxy, Varnish and FOSHttpCache

Every line includes the platform's HTTP cache bundle on top of FOSHttpCacheBundle: `EzSystemsPlatformHttpCacheBundle`
(eZ Platform 2.5 and 3.3) or `IbexaHttpCacheBundle` (4.6 and v5). It tags each response with the content it shows and,
when content is published, purges the affected pages. Where the purge goes is the **purge type**:

| Purge type | Purges | Use |
|---|---|---|
| `local` (the default on every line) | the Symfony reverse proxy's store (`AppCache` / `HttpCache`) in `var/cache/<env>/http_cache` | a single server without Varnish |
| `varnish` (`http` on the oldest lines) | a Varnish server, by tag, with the `xkey` module | production with Varnish |

### 6.7.1 What is wired per line

| Line | Purge type set by | Purge server | TTL | Symfony proxy in the front controller |
|---|---|---|---|---|
| 1.0.0.x | `purge_type: local` in `app/config/default_parameters.yml`, overridden by `HTTPCACHE_PURGE_TYPE` through `app/config/env/generic.php` | `HTTPCACHE_PURGE_SERVER` | `HTTPCACHE_DEFAULT_TTL` (86400) | yes: `app.php` wraps the kernel in `AppCache` unless `SYMFONY_HTTP_CACHE=0` or the environment is `dev` |
| 1.1.0.x, 1.2.0.x | `HTTPCACHE_PURGE_TYPE` (`env(HTTPCACHE_PURGE_TYPE): local` in `ezpublish.yaml` / `ibexa.yaml`) | `HTTPCACHE_PURGE_SERVER` | `HTTPCACHE_DEFAULT_TTL` | not in the shipped `public/index.php` |
| 1.3.0.x | `HTTPCACHE_PURGE_TYPE`, read by the kernel's core bundle (`IbexaCoreExtension::configureGenericSetup`) | `HTTPCACHE_PURGE_SERVER` | `HTTPCACHE_DEFAULT_TTL` | not in the shipped `public/index.php` |

On the three Symfony Runtime lines `public/index.php` returns the bare `App\Kernel`; nothing in the shipped files
reads `APP_HTTP_CACHE`, which the Apache example and older guides mention. With purge type `local` and no proxy in
front, responses carry their cache headers but nothing shared caches them. For shared HTTP caching on these lines,
put Varnish in front.

### 6.7.2 Varnish

1. Install Varnish with the `xkey` module from [varnish-modules](https://github.com/varnish/varnish-modules).
2. Use the VCL of the HTTP cache bundle that matches the line. For 1.0.0.x the repository ships
   [`doc/varnish/vcl/varnish4_xkey.vcl`](../varnish/vcl/varnish4_xkey.vcl) with
   [`parameters.vcl`](../varnish/vcl/parameters.vcl) (Varnish 5 or later, 6.0 LTS recommended in the file). For
   1.3.0.x the installed bundle carries `vendor/ibexa/http-cache/docs/varnish/vcl/varnish7.vcl` (Varnish 7.1 or
   later) with `parameters.vcl`; for 1.2.0.x take the VCL from the 4.6 branch of
   [ibexa/http-cache](https://github.com/ibexa/http-cache/tree/4.6/docs/varnish).
3. In `parameters.vcl`, point `backend` at the server that runs the application (Velocity, Apache or nginx) and list
   the addresses that may purge in `acl invalidators`.
4. Tell the application, in `.env.local`:

   ```bash
   HTTPCACHE_PURGE_TYPE=varnish
   HTTPCACHE_PURGE_SERVER=http://127.0.0.1:6081
   # only where an IP-based ACL is not possible:
   HTTPCACHE_VARNISH_INVALIDATE_TOKEN=<a long random value>
   ```

   On 1.0.0.x also set `SYMFONY_HTTP_CACHE=0`, so `AppCache` does not cache in front of Varnish.
5. Make the application trust Varnish as a proxy (section 6.10), so that it sees the client's scheme and address and
   answers the user-context hash requests Varnish sends.
6. Clear the application cache (`bin/console cache:clear --env=prod`) and restart the application server.

### 6.7.3 Purging by hand

```bash
# Everything the platform has tagged (all content pages):
php bin/console fos:httpcache:invalidate:tag ez-all --env=prod
# One path:
php bin/console fos:httpcache:invalidate:path /some/page --env=prod
# The whole proxy, where the proxy client supports it:
php bin/console fos:httpcache:clear --env=prod
```

`ez-all` is the tag the bundle puts on every response it tags (`ContentTagInterface::ALL_TAG` in the v5 bundle).
Operations routines for the cache are in [chapter 10](10-operations.md).

## 6.8 Docker

What the repository ships differs by line:

- **1.0.0.x and `master`**: [`doc/docker/`](../docker/) holds the upstream eZ Platform 2.5 Docker "building blocks"
  (`base-prod.yml`, `base-dev.yml`, `varnish.yml`, `solr.yml`, `redis.yml`, `db-postgresql.yml`, Dockerfiles for the
  app, nginx, Varnish and Solr). Its own [README](../docker/README.md) calls them unsupported blueprints made for test
  automation. The images are the old `ezsystems/php:7.3` / `7.4` images (the root `.env` selects
  `doc/docker/base-dev.yml` as `COMPOSE_FILE` and `PHP_IMAGE=ezsystems/php:7.3-v1`), so treat them as a reference for
  service wiring (for example how `varnish.yml` sets `HTTPCACHE_PURGE_TYPE=varnish` and `SYMFONY_HTTP_CACHE=0`), not
  as a ready runtime.
- **1.1.0.x, 1.2.0.x, 1.3.0.x**: no Docker directory. The root `compose.yaml` and `compose.override.yaml` are the
  Symfony Flex recipes of doctrine and mailer: a PostgreSQL 16 service named `database` (with a placeholder password
  you must change) and a MailCatcher service. There is no application, web server or Varnish container.

A container setup for these lines is therefore your own: one image with PHP 8 (the version of the line, see
[chapter 2](02-requirements.md)) running Velocity on `public/`, or PHP-FPM behind an nginx container, plus the database
of [chapter 7](07-databases.md). Mount `var/` and `public/var/` as volumes so caches, logs and uploaded files survive a
new image, and pass `APP_ENV`, `APP_SECRET` and the database URL as environment variables rather than baking
`.env.local` into the image. No such image is shipped or tested in this repository.

## 6.9 File permissions and ownership

The user that runs PHP for the web (the Velocity workers or the PHP-FPM pool) and the user that runs `bin/console`
(deploys, cron, Messenger workers) must both be able to write:

| Line | Writable directories |
|---|---|
| 1.1.0.x, 1.2.0.x, 1.3.0.x | `var/` (cache, logs, sessions, and an SQLite database file if used) and `public/var/` (uploaded files) |
| 1.0.0.x | `var/` (`var/cache`, `var/logs`, `var/sessions`), `web/var/`, and `ezpublish_legacy/var/` when the legacy bridge is installed |

Everything else should be readable but not writable by the web user. The method recommended by Symfony and by the
upstream installation guides is POSIX ACLs, which give both users access whatever the umask:

```bash
HTTPDUSER=www-data          # the PHP-FPM pool user or the Velocity worker user
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:"$(whoami)":rwX var public/var
sudo setfacl -R  -m u:"$HTTPDUSER":rwX -m u:"$(whoami)":rwX var public/var
```

(On 1.0.0.x use `var web/var`, plus `ezpublish_legacy/var` with the legacy bridge.) Without ACL support, run the web
workers and the console as the same user, or put both in one group and give the directories the setgid bit
(`chmod -R g+rwX var public/var` and `find var public/var -type d -exec chmod g+s {} +`).

Do not make the whole tree group-writable or executable with a blanket `chmod -R 755` / `775`; only the directories
above need write access, and files need no execute bit. The reference installation, for comparison, runs its PHP-FPM
pool as the domain user with the hosting panel's group, and its `public/var` is owned by that user.

## 6.10 HTTPS, reverse proxies and trusted proxies

When Velocity, Apache or nginx terminates TLS itself, PHP sees `HTTPS=on` and Symfony builds `https://` URLs on its
own. When anything sits in front (Varnish, a load balancer, Plesk's nginx in front of Apache, a CDN), Symfony must be
told which proxies to trust before it believes their `X-Forwarded-*` headers. Otherwise it generates `http://` links,
logs the proxy's address as the client, and Varnish's user-context hash requests are not answered.

| Line | How to trust proxies |
|---|---|
| 1.0.0.x | `SYMFONY_TRUSTED_PROXIES` in the environment of the web server or PHP-FPM pool: a comma-separated list, or `TRUST_REMOTE` to trust the direct peer (only when the application is not reachable from anywhere else); read by `web/app.php` |
| 1.1.0.x, 1.2.0.x | `framework.trusted_proxies` in `config/packages/framework.yaml`, e.g. `trusted_proxies: '%env(TRUSTED_PROXIES)%'` with `trusted_headers` set to the headers your proxy sends |
| 1.3.0.x | the same `framework.trusted_proxies` setting, or the variable `SYMFONY_TRUSTED_PROXIES` (Symfony 7.4's FrameworkBundle reads it by default) |

The shipped `.env` of 1.1.0.x to 1.3.0.x sets `TRUSTED_PROXIES=127.0.0.1`, but on 1.3.0.x nothing in the shipped
configuration or the installed kernel reads that variable (checked in the reference installation: no
`framework.trusted_proxies` in `config/packages/` and no reader in the kernel bundles). For 1.1.0.x and 1.2.0.x this
could not be checked without an installed `vendor/`; confirm on your installation with

```bash
php bin/console debug:config framework trusted_proxies --env=prod
```

and add the `framework.yaml` setting if it prints nothing useful.

Redirect HTTP to HTTPS at the outermost layer: Velocity serves both ports and can send `Strict-Transport-Security`
(`Q.webserver.hsts`); Apache uses a `*:80` virtual host with `Redirect permanent / https://www.example.com/`; nginx a
`return 301 https://$host$request_uri;` server. Start HSTS with a short `max-age` and raise it once every host name
of the site serves HTTPS correctly.

## 6.11 Known inaccuracies in the older server documents

Found while checking the files this chapter relies on:

- `doc/varnish/varnish.md` (master, 1.0.0.x) points to a `varnish5.vcl`; the directory ships `varnish4_xkey.vcl`.
- `doc/nginx/media-site.conf` uses a PHP 7.3 socket and `APP_ENV dev`; it names the trusted-proxies variable
  `APP_TRUSTED_PROXIES`, while the Apache example calls it `TRUSTED_PROXIES`. Neither is read by the shipped
  `public/index.php`.
- `doc/apache2/media-site-vhost.conf` and `doc/sevenx/INSTALL.md` (1.1.0.x to 1.3.0.x) set `APP_HTTP_CACHE`; the
  shipped Symfony Runtime front controller does not read it.
- `doc/sevenx/INSTALL.md` on 1.3.0.x, section 20, refers to `doc/varnish/`, which does not exist on that branch, and
  runs `fos:httpcache:invalidate:path / --all`; that command has no `--all` option (use the commands of
  section 6.7.3).
- `doc/INSTALL.md` (master) recommends `chown -R www-data:www-data .` and `chmod -R 755 .` over the whole project; see
  section 6.9 for the narrower set of writable directories.
- `doc/docker/README.md` and the root `.env` of 1.0.0.x describe the upstream eZ Platform Docker blueprints with
  PHP 7.3 images, which predate this project's PHP requirements.
- `public/index_cluster.php` on 1.3.0.x changes into `../ezpublish_legacy/` without checking that it exists, unlike
  `index_rest.php`.

## 6.12 Checklist

- [ ] The document root is `public/` (1.1.0.x to 1.3.0.x) or `web/` (1.0.0.x), never the project root.
- [ ] Every path that is not a file reaches `index.php` (or `app.php` on 1.0.0.x); only the front controller runs.
- [ ] `APP_ENV=prod` and `APP_DEBUG=0` (or `SYMFONY_ENV=prod` on 1.0.0.x) for production.
- [ ] Velocity: `-t` passes, the site file names `scripts`, the workers run as the user that owns `var/`, and the
      server is restarted after `.env.local` changes and deploys.
- [ ] HTTPS works on every host name; HTTP redirects to HTTPS.
- [ ] Behind a proxy: trusted proxies configured and checked with `debug:config`.
- [ ] HTTP cache: purge type and purge server match the setup; Varnish uses the VCL of the line with `xkey`.
- [ ] Velocity's response cache is off, or skips the platform's session cookies.
- [ ] `var/` and `public/var/` are writable by the web user and the console user, nothing else is.
- [ ] Uploads: the server's and PHP's body-size limits allow the files editors upload.

## 6.13 References

In this repository (paths relative to this chapter):

- Apache: [`doc/apache2/Readme.md`](../apache2/Readme.md), [`media-site-vhost.conf`](../apache2/media-site-vhost.conf),
  [`netgen-site-vhost.conf`](../apache2/netgen-site-vhost.conf), [`vhost.template`](../apache2/vhost.template)
- nginx: [`doc/nginx/Readme.md`](../nginx/Readme.md), [`media-site.conf`](../nginx/media-site.conf),
  [`netgen-site.conf`](../nginx/netgen-site.conf), [`ibexa_params.d/`](../nginx/ibexa_params.d/),
  [`ez_params.d/`](../nginx/ez_params.d/)
- Varnish: [`doc/varnish/varnish.md`](../varnish/varnish.md), [`vcl/varnish4_xkey.vcl`](../varnish/vcl/varnish4_xkey.vcl),
  [`vcl/parameters.vcl`](../varnish/vcl/parameters.vcl)
- Docker: [`doc/docker/README.md`](../docker/README.md), [`varnish.yml`](../docker/varnish.yml),
  [`base-prod.yml`](../docker/base-prod.yml)
- Front controllers on the line branches:
  [1.3.0.x `public/index.php`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/public/index.php),
  [1.3.0.x `public/index_rest.php`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/public/index_rest.php),
  [1.0.0.x `web/app.php`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.0.0.x/web/app.php)
- HTTP cache configuration on the line branches:
  [1.3.0.x `config/packages/ibexa.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/ibexa.yaml),
  [1.3.0.x `config/packages/ibexa_http_cache.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/config/packages/ibexa_http_cache.yaml),
  [1.1.0.x `config/packages/fos_http_cache.yaml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.1.0.x/config/packages/fos_http_cache.yaml),
  [1.0.0.x `app/config/default_parameters.yml`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.0.0.x/app/config/default_parameters.yml)
- The line installation guide: [1.3.0.x `doc/sevenx/INSTALL.md`](https://github.com/se7enxweb/exponential-platform-nexus/blob/1.3.0.x/doc/sevenx/INSTALL.md)
- Other chapters: [2. Requirements](02-requirements.md), [4. Installing](04-installing.md),
  [7. Databases](07-databases.md), [8. Configuration](08-configuration.md),
  [9. Front end and themes](09-frontend-and-themes.md), [10. Operations](10-operations.md)

Exponential Velocity:

- <https://github.com/se7enxweb/exponential-velocity>, in particular
  [docs/configuration.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/configuration.md),
  [docs/https.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/https.md),
  [docs/cache.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/cache.md),
  [docs/layout.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/layout.md),
  [docs/reset.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/reset.md),
  [docs/migrate-apache.md](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/migrate-apache.md),
  [packaging/systemd/exponential-velocity.service](https://github.com/se7enxweb/exponential-velocity/blob/main/packaging/systemd/exponential-velocity.service)
- The Exponential 6 book: [Contents](https://github.com/se7enxweb/exponential/blob/main/doc/install/README.md),
  [chapter 8, Serving the site](https://github.com/se7enxweb/exponential/blob/main/doc/install/08-serving-the-site.md)

Symfony and PHP:

- [Configuring a web server](https://symfony.com/doc/current/setup/web_server_configuration.html),
  [Setting up file permissions](https://symfony.com/doc/current/setup/file_permissions.html),
  [The Runtime component](https://symfony.com/doc/current/components/runtime.html)
- Proxies: [current](https://symfony.com/doc/current/deployment/proxies.html),
  [5.4](https://symfony.com/doc/5.4/deployment/proxies.html), [3.4](https://symfony.com/doc/3.4/deployment/proxies.html);
  [FrameworkBundle configuration](https://symfony.com/doc/current/reference/configuration/framework.html)
- [HTTP cache](https://symfony.com/doc/current/http_cache.html)
- PHP: [FPM configuration](https://www.php.net/manual/en/install.fpm.configuration.php); Apache:
  [mod_proxy_fcgi](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html)

Upstream platform and cache documentation:

- Installation guides: [eZ Platform 2.5](https://doc.ezplatform.com/en/2.5/getting_started/install_ez_platform/),
  [3.3](https://doc.ibexa.co/en/3.3/getting_started/install_ez_platform/),
  [Ibexa 4.6](https://doc.ibexa.co/en/4.6/getting_started/install_ibexa_dxp/),
  [Ibexa 5.0](https://doc.ibexa.co/en/5.0/getting_started/install_ibexa_dxp/)
- Reverse proxy: [3.3 HTTP cache](https://doc.ibexa.co/en/3.3/guide/cache/http_cache/),
  [4.6](https://doc.ibexa.co/en/4.6/infrastructure_and_maintenance/cache/http_cache/reverse_proxy/),
  [5.0](https://doc.ibexa.co/en/5.0/infrastructure_and_maintenance/cache/http_cache/reverse_proxy/);
  VCL: [ezplatform-http-cache 1.0](https://github.com/ezsystems/ezplatform-http-cache/tree/1.0/docs/varnish),
  [ibexa/http-cache 4.6](https://github.com/ibexa/http-cache/tree/4.6/docs/varnish),
  [ibexa/http-cache v5.0.0](https://github.com/ibexa/http-cache/tree/v5.0.0/docs/varnish)
- Security checklists: [4.6](https://doc.ibexa.co/en/4.6/infrastructure_and_maintenance/security/security_checklist/),
  [5.0](https://doc.ibexa.co/en/5.0/infrastructure_and_maintenance/security/security_checklist/)
- FOSHttpCache: [bundle](https://foshttpcachebundle.readthedocs.io/en/latest/),
  [Varnish configuration](https://foshttpcache.readthedocs.io/en/latest/varnish-configuration.html);
  Varnish: [documentation](https://varnish-cache.org/docs/), [varnish-modules (xkey)](https://github.com/varnish/varnish-modules)
- Netgen Layouts: [documentation](https://docs.netgen.io/projects/layouts/en/latest/)
- Docker Compose: [documentation](https://docs.docker.com/compose/)

[Previous: 5. The demo site and Netgen Layouts](05-the-demo-site-and-layouts.md) · [Next: 7. Databases](07-databases.md) · [Contents](README.md)
