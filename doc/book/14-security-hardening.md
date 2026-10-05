# 14. Security hardening

An Exponential Platform Nexus installation that works is not yet one that is safe to put on the internet. Each line
ships with demonstration defaults: placeholder secrets, the development environment switched on, demo host names, a
known administrator password, and on the older lines a legacy kernel with its own view of proxies and caches. This
chapter goes through what to change before go-live, layer by layer, for all four lines: what the web server must
never hand out, secrets, debug mode, the administration interfaces, trusted proxies on the Symfony side and in the
legacy kernel, HTTP cache safety, sessions, headers, uploads, Exponential Velocity, and keeping up to date. It ends
with a go-live checklist.

[Previous: 13. Troubleshooting](13-troubleshooting.md) · [Contents](README.md)

---

## Contents of this chapter

1. [The layers at a glance](#141-the-layers-at-a-glance)
2. [What the web server must never hand out](#142-what-the-web-server-must-never-hand-out)
3. [Secrets](#143-secrets)
4. [Debug mode and the environment](#144-debug-mode-and-the-environment)
5. [The administration interfaces](#145-the-administration-interfaces)
6. [Behind a proxy: trusted proxies](#146-behind-a-proxy-trusted-proxies)
7. [HTTP cache safety](#147-http-cache-safety)
8. [Sessions, cookies and forms](#148-sessions-cookies-and-forms)
9. [Security headers and HTTPS](#149-security-headers-and-https)
10. [Uploads and the storage directory](#1410-uploads-and-the-storage-directory)
11. [Exponential Velocity](#1411-exponential-velocity)
12. [Keeping up to date and reporting problems](#1412-keeping-up-to-date-and-reporting-problems)
13. [Go-live checklist](#1413-go-live-checklist)
14. [References](#1414-references)

Where a statement describes what the shipped code does, it was read from the files of the branch named (`git show
<branch>:<path>`), on 5 October 2026; entries marked "read from the code" were not reproduced against a running site.

---

## 14.1 The layers at a glance

| Layer | What can go wrong | Section |
|---|---|---|
| Web server | Project root served instead of `public/` or `web/`; PHP executed from the upload directory | [14.2](#142-what-the-web-server-must-never-hand-out) |
| Configuration | Placeholder `APP_SECRET`, JWT passphrase, purge token; secrets committed | [14.3](#143-secrets) |
| Environment | `APP_ENV=dev` from the shipped `.env`; on 1.0.0.x a `Host` header that switches to `dev` | [14.4](#144-debug-mode-and-the-environment) |
| Administration | Default password, admin, Layouts editor, REST and GraphQL reachable from anywhere | [14.5](#145-the-administration-interfaces) |
| Proxies | Forwarded headers believed from anyone, or not believed from the real proxy | [14.6](#146-behind-a-proxy-trusted-proxies) |
| HTTP cache | Private pages marked public; purge open to anyone | [14.7](#147-http-cache-safety) |
| Browser | Session cookies without `Secure`; missing security headers | [14.8](#148-sessions-cookies-and-forms), [14.9](#149-security-headers-and-https) |
| Files | Executable uploads; writable code | [14.10](#1410-uploads-and-the-storage-directory) |
| Server | Velocity configuration, symbolic links, panel password | [14.11](#1411-exponential-velocity) |

## 14.2 What the web server must never hand out

- **The document root is `public/`** on 1.1.0.x to 1.3.0.x and **`web/`** on 1.0.0.x, never the project root. The
  project root holds `.env` and `.env.local` (database and mail credentials, `APP_SECRET`), `config/` or
  `app/config/`, `config/jwt/*.pem` (the REST API's private key), `var/` (logs, sessions, cache, an SQLite database)
  and `vendor/`. A web server pointed at the project root gives all of that away.
- **No PHP from the storage directory.** The shipped rules refuse script extensions below `var/`:
  `RewriteRule ^var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ - [F]` in the 1.0.0.x `.htaccess` files and
  `location ~ ^/var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ { return 403; }` in `doc/nginx/media-site.conf`. Keep
  them when you write your own configuration ([chapter 6](06-serving-the-site.md)).
- **Only the front controller runs.** Requests for any other `.php` file must not reach PHP. On 1.0.0.x `web/app_dev.php`
  exists; it refuses requests that carry `X-Forwarded-For` or `Client-IP` or do not come from `127.0.0.1`/`::1`, and
  the shipped `.htaccess` redirects `app_*.php` URLs away. Delete it on production servers anyway: a local reverse
  proxy that does not add `X-Forwarded-For` would pass its check.
- **No directory listings**, no `.git`: the 1.0.0.x `.htaccess` sets `Options -Indexes` and redirects `.git` and
  `.svn` paths. Do the same in nginx and Velocity configurations, and deploy without the `.git` directory where you
  can.
- **Reject unknown host names.** Give the site its own virtual host with exact `ServerName`/`server_name` entries and
  make the default virtual host answer `403` or close the connection. Several things in these lines decide by host
  name ([14.4](#144-debug-mode-and-the-environment), [14.7](#147-http-cache-safety)); a server that accepts any `Host`
  header lets a visitor choose. On 1.0.0.x you can also set `framework.trusted_hosts` (the shipped `app/config/config.yml`
  has `trusted_hosts: ~`); on the later lines the same key exists under `framework:`:

  ```yaml
  framework:
      trusted_hosts: ['^www\.example\.com$', '^admin\.example\.com$']
  ```

## 14.3 Secrets

What each line ships, and what you must replace:

| Secret | 1.0.0.x | 1.1.0.x, 1.2.0.x | 1.3.0.x |
|---|---|---|---|
| Framework secret | `env(SYMFONY_SECRET): ThisEzPlatformTokenIsNotSoSecret_PleaseChangeIt` in `app/config/parameters.yml.dist` | `APP_SECRET=ThisTokenIsNotSoSecretChangeIt` in `.env` | `APP_SECRET=` (empty) in `.env` |
| JWT key passphrase | not used | `JWT_PASSPHRASE=ThisTokenIsNotSoSecretChangeIt` | same |
| Varnish purge token | none | `HTTPCACHE_VARNISH_INVALIDATE_TOKEN=` (empty) | same |
| Database credentials | `parameters.yml` | `DATABASE_URL` (the `.env` example is a placeholder) | same |
| Mail, reCAPTCHA, Sentry, MailerLite | `parameters.yml` | `MAILER_DSN`, `GOOGLE_RECAPTCHA_*`, `SENTRY_DSN`, `MAILER_LITE_API_KEY` | same |

The framework secret signs CSRF tokens, remember-me cookies, signed URLs and fragment URIs. A placeholder that is
public in a repository is as good as no secret. Generate one per installation:

```bash
openssl rand -hex 32
```

Where to put secrets:

- **1.0.0.x**: in `app/config/parameters.yml` (ignored by Git; `parameters.yml.dist` is the template) or as real
  environment variables of the PHP-FPM pool (`env[SYMFONY_SECRET] = ...`), which the `env(...)` parameters read.
- **1.1.0.x and later**: in `.env.local` (ignored by Git on 1.2.0.x and 1.3.0.x; check `.gitignore` on 1.1.0.x, whose
  file does not list it, and add `/.env.local` and `/.env.*.local` yourself), in the real environment, or in the
  Symfony secrets vault:

  ```bash
  php bin/console secrets:generate-keys --env=prod
  php bin/console secrets:set APP_SECRET --random --env=prod
  php bin/console secrets:set DATABASE_URL --env=prod
  ```

  Never commit `config/secrets/prod/prod.decrypt.private.php`; 1.2.0.x and 1.3.0.x already ignore it. Deploy it
  separately, or give the server `SYMFONY_DECRYPTION_SECRET` instead.
- For production speed, `composer dump-env prod` compiles the `.env` files into `.env.local.php`; that file holds the
  secrets in clear text, so treat it like `.env.local`.

What changing the secret does: existing CSRF tokens and remember-me cookies become invalid, so forms open in a
browser fail once and users with "remember me" sign in again. Change it before go-live, and again if it ever leaked.

The JWT key pair under `config/jwt/` (1.1.0.x and later) is created with the passphrase from `JWT_PASSPHRASE`; set
your own passphrase first, then `php bin/console lexik:jwt:generate-keypair`. The `.pem` files are ignored by Git on
every line that has them.

The legacy kernel (1.0.0.x to 1.2.0.x) takes its database connection from the Symfony side through the bridge; any
INI overrides with credentials you add for legacy scripts belong in the project's legacy settings directory, outside
the document root, and out of Git.

## 14.4 Debug mode and the environment

**1.1.0.x to 1.3.0.x.** The shipped `.env` sets `APP_ENV=dev`. Without an override, a production server runs in the
development environment: the web profiler and debug bundles are registered for `dev` (`WebProfilerBundle`,
`DebugBundle`, the platform and Layouts debug bundles in `config/bundles.php`), exceptions show stack traces with
file paths and configuration, and the profiler stores every request under `var/cache/dev/profiler/`. Set the
environment in `.env.local` or in the PHP-FPM pool:

```bash
APP_ENV=prod
APP_DEBUG=0
```

```bash
php bin/console about --env=prod | grep -E 'Environment|Debug'
curl -s -o /dev/null -w '%{http_code}\n' https://www.example.com/_profiler    # must not be 200
```

**1.0.0.x.** `web/app.php` takes the environment from `SYMFONY_ENV` (default `prod`, and the shipped `.htaccess`
sets `prod`), but it contains a project patch that runs before that:

```php
// PATCH JAC -  example.com =>  dev.example.com or example-dev.com  => enabled DEV mode
if ( str_contains( $_SERVER['HTTP_HOST'], 'dev.' ) ) {
    putenv( 'SYMFONY_ENV=dev' );
}
```

Any request whose `Host` header contains `dev.` (also `mydev.example.com`, or a forged header) runs in the `dev`
environment with debugging on, if the web server lets that host name reach the site (read from the code). Either
remove the block from `web/app.php` on production servers, or make sure the virtual host accepts only your exact
production names ([14.2](#142-what-the-web-server-must-never-hand-out)). It also loads `.env.php` from the project
root when that file exists; keep that file out of the document root, as it is.

**PHP itself**, on every line: `display_errors=Off` and `display_startup_errors=Off` in production,
`log_errors=On`, and `zend.exception_ignore_args=On`, so stack traces in logs do not carry argument values such as
passwords.

## 14.5 The administration interfaces

What each line exposes:

| Line | Interfaces | How they are reached in the shipped configuration |
|---|---|---|
| 1.0.0.x | Symfony admin (`admin`), Netgen admin UI (`ngadminui`), legacy admin (`legacy_admin`), Layouts (`/nglayouts/`), REST, GraphQL | `ngadminui` by URI `/ngadminui`; `admin` and `legacy_admin` by host name (`app/config/ezplatform_siteaccess.yml`) |
| 1.1.0.x, 1.2.0.x | `adminui`, `ngadminui`, `legacy_admin`, Layouts, REST, GraphQL | by URI: `/adminui/`, `/ngadminui/`, `/legacy_admin/` |
| 1.3.0.x | `adminui`, Layouts, REST, GraphQL | by URI: `/adminui/` |

Steps:

1. **Change the administrator's password** at the first sign-in. The demo data of every line installs a user `admin`
   whose password is published in this repository's README. Remove or disable demo users you do not need.
2. **Replace the demo host map** of 1.0.0.x (`Map\Host` in `app/config/ezplatform_siteaccess.yml` and the lists in
   `app/config/http_cache.yml`) with your own host names. As shipped, they name the project's own demo servers.
3. **Put the administration on its own host name** (for example `admin.example.com`), matched by host, and restrict
   that host at the web server: by network (VPN, office ranges) or with an additional HTTP authentication layer. A
   URI-matched admin (`/adminui/`) is reachable on every host the public site answers on; if you keep URI matching,
   restrict the path prefixes at the web server instead:

   ```nginx
   location ~ ^/(adminui|ngadminui|legacy_admin|nglayouts)(/|$) {
       allow 192.0.2.0/24;
       deny all;
       try_files $uri /index.php$is_args$args;
   }
   ```

4. **Netgen Layouts** lives under `/nglayouts` (`netgen_layouts.route_prefix`). Access is decided by repository
   policies of the module `nglayouts`: `nglayouts/admin`, `nglayouts/editor` and `nglayouts/api` stand for
   `ROLE_NGLAYOUTS_ADMIN`, `ROLE_NGLAYOUTS_EDITOR` and `ROLE_NGLAYOUTS_API`. Give editors only `editor` and `api`;
   keep `admin` (layout types, rules, shared layouts) for a few people.
5. **REST and GraphQL** (`/api/ezp/v2/`, `/graphql`) answer with the permissions of the signed-in user, but they
   accept requests from anywhere. If no external client uses them, restrict them like the admin; if one does, use
   JWT (1.1.0.x and later) and the CORS setting `CORS_ALLOW_ORIGIN` (the shipped value allows only `localhost`).
6. **Roles**: keep the anonymous role to `content/read` on the public sections and `user/login` on the public
   siteaccesses. Review every role after importing demo data; the demo grants are for a demonstration.

## 14.6 Behind a proxy: trusted proxies

A TLS terminator, Varnish, a load balancer or a CDN in front of the site tells the application what the visitor asked
for in `X-Forwarded-*` headers. A visitor can send the same headers. The application must believe them only from the
proxies you name, and must believe them from those, or it builds `http://` URLs, mis-detects the client address, and
caches under the wrong scheme. Nexus has up to three places that decide this.

### The Symfony side

**1.0.0.x (Symfony 3.4).** `web/app.php` reads `SYMFONY_TRUSTED_PROXIES` (a comma-separated list) and calls
`Request::setTrustedProxies(..., Request::HEADER_X_FORWARDED_ALL)`. The special value `TRUST_REMOTE` trusts whatever
address connects, which is only safe when nothing but the proxy can reach the PHP server. Set it in the PHP-FPM pool
or the virtual host:

```apache
SetEnv SYMFONY_TRUSTED_PROXIES "127.0.0.1"
```

**1.1.0.x and 1.2.0.x (Symfony 5.4).** The shipped `.env` contains `TRUSTED_PROXIES=127.0.0.1`, but no configuration
file of these lines reads that variable, and `public/index.php` (Symfony Runtime) does not either. Add the setting
(Symfony 5.2 introduced `trusted_proxies` and `trusted_headers` as framework options). On 1.2.0.x put it in
`config/packages/framework.yaml`; on 1.1.0.x the `framework:` block is in `config/packages/ezpublish.yaml`:

```yaml
framework:
    trusted_proxies: '%env(TRUSTED_PROXIES)%'
    trusted_headers: ['x-forwarded-for', 'x-forwarded-proto', 'x-forwarded-port']
```

**1.3.0.x (Symfony 7.4).** The same configuration works. In addition, Symfony 7.4's framework defaults read
`SYMFONY_TRUSTED_PROXIES` and `SYMFONY_TRUSTED_HEADERS` from the environment (`%env(default::SYMFONY_TRUSTED_PROXIES)%`
in the framework configuration), and accept `private_ranges` as a value. `TRUSTED_PROXIES` in `.env` is still not
read unless you add the configuration above.

Notes for all three:

- List only the addresses or ranges of your proxies (`127.0.0.1`, `10.0.0.0/8`, the published ranges of a CDN).
- Leave `x-forwarded-host` out of the trusted headers unless the proxy rewrites the host: trusting it lets a client
  that reaches the proxy choose the host name the application uses for absolute URLs.
- Check it: a request sent directly with a forged header must change nothing.

  ```bash
  curl -s -o /dev/null -w '%{redirect_url}\n' -H 'X-Forwarded-Proto: https' http://www.example.com/login
  ```

### The legacy kernel (1.0.0.x to 1.2.0.x)

The legacy kernel reads the forwarded headers itself (for HTTPS detection, the host name, the client address of
`DebugByIP`, `TrustedIPList` and the audit log). From Exponential 6.0.15 it believes them only from the proxies listed
in `site.ini [HTTPHeaderSettings] TrustedProxies[]` (default: `127.0.0.1` and `::1`), and reads `X-Forwarded-For` from
the right. On 5 October 2026 this is on the `main` branch of `se7enxweb/exponential`; the newest release tag is
`v6.0.14`, which does not have it. A site gets it once the installed `se7enxweb/exponential` includes the change:

```bash
grep -n "TrustedProxies" ezpublish_legacy/settings/site.ini
```

Set the list in your legacy override settings (kept in the project and linked into `ezpublish_legacy/settings/`):

```ini
[HTTPHeaderSettings]
TrustedProxies[]
TrustedProxies[]=127.0.0.1
TrustedProxies[]=10.0.0.0/16
ClientIpByCustomHTTPHeader=X-Forwarded-For
```

The whole behaviour, with examples for Apache, nginx, cloud load balancers and CDNs, is in
[Forwarded headers are trusted only from configured proxies](https://github.com/se7enxweb/exponential/blob/main/doc/bc/6.0/trusted-proxies.md).
Keep the Symfony list and the legacy list the same.

### Exponential Velocity

When Exponential Velocity serves the site, it works out the visitor's address itself from its own list of trusted
proxies (`Q.webserver.proxy.trusted`, in `/etc/vc/conf-available/reverse-proxy.conf`; `127.0.0.1` and `::1` by
default) and passes the visitor's address to PHP as `REMOTE_ADDR`; on its TLS listener it sets `HTTPS=on` itself.
Configure proxies in front of Velocity there, and let them pass the original `Host` header.

## 14.7 HTTP cache safety

### The 1.0.0.x AppCache rewrites cache headers

On 1.0.0.x the Symfony reverse proxy is enabled outside `dev` (`SYMFONY_HTTP_CACHE` unset), and the class used is the
project's `app/AppCache.php` (loaded through the `classmap` in `composer.json`). After the response is built, it
**replaces** `Cache-Control` headers that contain `private` or `no-cache` without `public` and `s-maxage` by
`public, max-age=3600, s-maxage=3600, must-revalidate`, unless one of these applies (read from the code):

- the host name is in `http_cache.uncached_hostnames` of `app/config/http_cache.yml`;
- the siteaccess found for the host name through the `Map\Host` list of `app/config/ezplatform_siteaccess.yml` is in
  `http_cache.uncached_siteaccesses`;
- the path matches one of `http_cache.uncached_url_patterns` (shipped: `^/admin`, `^/api/`, `^/login`, `^/logout`,
  `^/user`, `^/_`, `^/ngadminui`, `^/ng/`);
- the response sets a cookie, or carries an `X-User-Context-Hash` header.

What can go wrong: a page that the platform marked private, because it depends on the signed-in user or carries a
form token, leaves the server as public for an hour when its host and path are not in those lists. The Symfony
proxy decides what to store before the rewrite, but the browser, and any Varnish, CDN or company proxy in front, sees
the public header. The shipped lists name the project's own demo hosts, and the path patterns do not include
`/nglayouts`, `/graphql` or `/legacy_admin`. Before go-live on 1.0.0.x:

1. Put your own admin and editor host names into `uncached_hostnames`, and keep `uncached_siteaccesses` in step with
   your siteaccess names.
2. Add the paths of everything user-specific that is not covered: at least `^/nglayouts`, `^/graphql`, `^/legacy_admin`,
   `^/cb/` (the Content Browser), and the paths of your forms and member pages.
3. Test with a signed-in browser session and a page that greets the user: its `Cache-Control` must stay `private`.

### On every line

- **Varnish purges must be authenticated.** The shipped VCL parameters (`doc/varnish/vcl/parameters.vcl` on 1.0.0.x)
  allow purges from `127.0.0.1` and `192.168.0.0/16` (`acl invalidators`); narrow that to your application servers.
  On 1.1.0.x and later set `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` to a random value and the same value in the VCL.
- **User context.** Pages vary by the user context hash (`X-User-Context-Hash`); never strip the `Vary` header in the
  proxy, or users with different rights share cached pages.
- **Compression of responses with secrets.** The vendor stopped compressing REST and JSON responses in 3.3.41 and
  4.6.14 because of BREACH. The shipped nginx example compresses only static types and notes the risk for
  `text/html`; the 1.0.0.x `.htaccess` deflates `text/html` and `application/json`. Do not compress responses that
  contain both a secret (CSRF token) and attacker-controlled input over TLS, or pad them.
- **Purge after permission changes.** Role and section changes do not change cached pages; purge the cache.

## 14.8 Sessions, cookies and forms

- **Secure cookies.** 1.2.0.x sets `session.cookie_secure: auto` and `cookie_samesite: lax` in
  `config/packages/framework.yaml`; `auto` sets `Secure` when the request is HTTPS, which depends on trusted proxies
  being right ([14.6](#146-behind-a-proxy-trusted-proxies)). On 1.0.0.x `app/config/config.yml` sets
  `cookie_httponly: true`; add `cookie_secure: true` when the site is HTTPS only. On 1.1.0.x and 1.3.0.x set the same
  keys under `framework.session` if they are not there.
- **Session storage.** 1.1.0.x and later store sessions in `var/sessions/<env>/` (`SESSION_SAVE_PATH`); the directory
  must not be readable by other system users. With several application servers use a shared handler (Redis), as
  [chapter 10](10-operations.md) describes.
- **CSRF.** The login form of 1.0.0.x uses the `authenticate` token (`app/config/security.yml`); 1.2.0.x enables
  `csrf_protection: true`. Keep CSRF protection on for your own forms.
- **Brute force.** The lines ship no login throttling of their own. Limit `/login` (and the legacy login of
  `legacy_admin`) at the web server or with Symfony's login throttling (`login_throttling` in the firewall, available
  on Symfony 5.2 and later, which needs `symfony/rate-limiter`).

## 14.9 Security headers and HTTPS

The applications set few headers themselves. Set them at the front:

| Header | Value to start from |
|---|---|
| `Strict-Transport-Security` | `max-age=31536000` (start lower, add `includeSubDomains` only when every subdomain is HTTPS) |
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `SAMEORIGIN` (the Layouts editor and the admin preview use frames on the same origin) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Content-Security-Policy` | build it per site; the admin interfaces need `'unsafe-inline'` scripts and styles |

The 1.0.0.x variant `src/AppBundle/Resources/symlink/root_prod/.htaccess` sets these (with a two-year HSTS and a CSP
of `default-src https: 'unsafe-eval' 'unsafe-inline'`); the `web/.htaccess` link in the repository points at the
`root_dev` variant, which sets `SYMFONY_ENV=prod` but no headers. Point the link at `root_prod`, or set the headers in
the virtual host. For Velocity, see [14.11](#1411-exponential-velocity).

Redirect HTTP to HTTPS for every host, admin first.

## 14.10 Uploads and the storage directory

- Uploaded files land below `public/var/<site>/storage/` (`web/var/...` and the legacy `ezpublish_legacy/var/...` on
  1.0.0.x). The web server serves images from there directly; keep the script rule of [14.2](#142-what-the-web-server-must-never-hand-out).
- SVG files can carry scripts. The shipped rules serve `storage/original/image/*.svg` directly; if editors you do not
  trust can upload SVG, serve them with `Content-Security-Policy: script-src 'none'` or as downloads.
- Code and configuration must not be writable by the web server's user. Only `var/` and the storage directories are
  ([chapter 6](06-serving-the-site.md)); a blanket `chmod -R 775` on the project gives an attacker who can write one
  file the ability to change code.
- Limit upload sizes at the web server (`client_max_body_size 48m` in the nginx example) and in PHP
  (`upload_max_filesize`, `post_max_size`).

## 14.11 Exponential Velocity

Exponential Velocity is the recommended way to serve Exponential sites, also in front of a Nexus installation:
it runs the PHP application itself (a `symfony` preset with `public/` as the root exists) and serves HTTP and HTTPS
without a separate web server. [Chapter 6](06-serving-the-site.md) shows how to run Nexus with it. What matters for
security:

- **Nothing outside the document root.** Velocity refuses, with 403, any file whose real path, after every symbolic
  link is followed, lies outside the document root; `Q.webserver.followSymlinks` (default `false`) turns that off.
  Nexus uses symbolic links: `assets:install --symlink` links `public/bundles/*` into `vendor/`, and on 1.0.0.x the
  storage directory is a link into `src/AppBundle/`. Under Velocity those files answer 403. Either install bundle
  assets as copies (`php bin/console assets:install public` without `--symlink`) and move storage under the document
  root, or turn `followSymlinks` on knowing that any link under the root then serves its target.
- **Headers on every response.** Velocity adds the headers of `Q.webserver.headers` to every response it builds,
  static files included, and with `headersOnScripts` to PHP responses that did not set them; `Q.webserver.hsts` adds
  `Strict-Transport-Security` on TLS only:

  ```json
  { "Q": { "webserver": {
      "headers": {
          "X-Content-Type-Options": "nosniff",
          "X-Frame-Options": "SAMEORIGIN",
          "Referrer-Policy": "strict-origin-when-cross-origin"
      },
      "headersOnScripts": true,
      "hsts": { "maxAge": 300 }
  } } }
  ```

  Start HSTS low and raise it once every host name works over HTTPS: browsers that saw it refuse plain HTTP for that
  long.
- **Trusted proxies** in front of Velocity are configured in Velocity ([14.6](#146-behind-a-proxy-trusted-proxies)).
- **The control panel** changes the server; its password must meet strict rules (16 or more characters, four
  character classes) and its credential store must belong to root or the server's user. Set the password before the
  server is reachable, and keep the panel off the public interface.
- **Persistent workers.** A worker that keeps the application in memory keeps its secrets and container there too;
  after changing `.env.local`, secrets or configuration, restart the workers, not only `cache:clear`.

The Velocity documents: [security](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/security.md),
[headers](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/headers.md) and
[passwords](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/passwords.md); the Exponential book's
[hardening chapter](https://github.com/se7enxweb/exponential/blob/main/doc/install/13-security-hardening.md) has a
section on Velocity-specific hardening for the legacy kernel.

## 14.12 Keeping up to date and reporting problems

- Follow the releases of your line ([chapter 11.9](11-upgrading-between-lines.md#119-patch-updates-inside-a-line)).
  The platform, Symfony and Netgen packages are forks maintained by se7enxweb; their fixes arrive through
  `composer update` within the line's constraints.
- Check dependencies for published advisories:

  ```bash
  composer audit          # Composer 2.4 and later
  ```

  The 1.0.0.x Composer scripts also run `bin/security-checker security:check`.
- Report vulnerabilities privately to the address in [SECURITY.md](../../SECURITY.md), not in a public issue.

## 14.13 Go-live checklist

- [ ] Document root is `public/` (`web/` on 1.0.0.x); `.env*`, `config/`, `var/`, `vendor/` are not reachable.
- [ ] Scripts below `var/` refused; `app_dev.php` removed (1.0.0.x); no directory listings.
- [ ] Unknown host names refused by the web server; `trusted_hosts` set.
- [ ] `APP_SECRET` (`SYMFONY_SECRET` on 1.0.0.x), `JWT_PASSPHRASE`, purge token and database password replaced; none
      of them in Git.
- [ ] `APP_ENV=prod`, `APP_DEBUG=0`; `/_profiler` not reachable; on 1.0.0.x the `dev.` host patch removed or unreachable.
- [ ] `display_errors=Off`, `zend.exception_ignore_args=On`.
- [ ] Administrator password changed; demo users removed; roles reviewed.
- [ ] Demo host maps replaced (1.0.0.x); admin, Layouts, REST and GraphQL restricted to who needs them.
- [ ] Trusted proxies set on the Symfony side, in the legacy kernel (1.0.0.x to 1.2.0.x) and in Velocity, and tested
      with a forged header.
- [ ] 1.0.0.x AppCache lists cover your admin hosts and every user-specific path; a signed-in page stays `private`.
- [ ] Varnish purges limited to the application servers; token set.
- [ ] Session cookies `Secure` and `HttpOnly`; HTTPS redirect for every host; security headers set.
- [ ] Code not writable by the web server; uploads limited.
- [ ] Velocity: symbolic link behaviour decided, headers configured, panel password set.
- [ ] `composer audit` clean or reviewed.

## 14.14 References

In this repository (read per branch with `git show <branch>:<path>`):

- `web/app.php`, `web/app_dev.php`, `app/AppCache.php`, `app/config/http_cache.yml`, `app/config/config.yml`,
  `app/config/security.yml`, `app/config/parameters.yml.dist`, `src/AppBundle/Resources/symlink/root_prod/.htaccess`
  (1.0.0.x, `master`)
- `.env`, `.gitignore`, `config/bundles.php`, `config/packages/framework.yaml` (1.1.0.x to 1.3.0.x)
- [doc/apache2](../apache2/), [doc/nginx](../nginx/), [doc/varnish](../varnish/) (1.0.0.x)
- [SECURITY.md](../../SECURITY.md)

Exponential:

- [Forwarded headers are trusted only from configured proxies](https://github.com/se7enxweb/exponential/blob/main/doc/bc/6.0/trusted-proxies.md)
- [Exponential book, chapter 13: Security hardening](https://github.com/se7enxweb/exponential/blob/main/doc/install/13-security-hardening.md)
- Exponential Velocity: [security](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/security.md),
  [headers](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/headers.md),
  [passwords](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/passwords.md)

Symfony and PHP:

- [Configuring Symfony to work behind a load balancer or a reverse proxy](https://symfony.com/doc/current/deployment/proxies.html)
  (current), [5.x](https://symfony.com/doc/5.x/deployment/proxies.html), [3.x](https://symfony.com/doc/3.x/deployment/proxies.html)
- [framework.secret](https://symfony.com/doc/current/reference/configuration/framework.html#secret),
  [secrets management](https://symfony.com/doc/current/configuration/secrets.html)
- [Security: login throttling](https://symfony.com/doc/current/security.html#limiting-login-attempts)
- [PHP: zend.exception_ignore_args](https://www.php.net/manual/en/ini.core.php#ini.zend.exception-ignore-args)

Platform:

- [Security checklist](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/security/security_checklist/)
- [Reverse proxy](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/http_cache/reverse_proxy/)
