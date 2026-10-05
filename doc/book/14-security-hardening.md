# 14. Security hardening

An Exponential Platform Nexus installation that works is not yet one that is safe to put on the internet. Each line
ships with demonstration defaults: placeholder secrets, the development environment switched on, demo host names, a
known administrator password, and on the older lines a legacy kernel with its own view of proxies and caches. This
chapter goes through what to change before go-live, layer by layer, for all four lines: what the web server must
never hand out, secrets, debug mode, the administration interfaces, trusted proxies on the Symfony side, in the
legacy kernel and in Exponential Velocity, HTTP cache safety, sessions, headers, uploads, Velocity itself, and keeping
up to date. It ends with a go-live checklist.

Several of the weaknesses this chapter used to describe were fixed in the code of the branches on 5 October 2026: the
`Host` header switch to the `dev` environment, the 2.5 generation's cache header rewriting, the unread
`TRUSTED_PROXIES` variable and the unread `APP_HTTP_CACHE` variable. **None of those fixes is in a release tag yet**:
the newest tags (`v2.5.0.6` and `1.0.0.10`, `v1.1.0.7`, `v1.2.0.0`, `1.3.0.5`) still behave the old way. Each section
therefore says what the branch does now, what a release installation does, and how to tell which one you run.

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
<branch>:<path>`) on 5 October 2026; entries marked "read from the code" were not reproduced against a running site.
The Velocity statements were read from the engine code and checked with the engine's own tests
(`tests/unit-symlink-containment.php`, `tests/unit-proxy-proto.php`). "1.0.0.x" means the 2.5 generation on either of
its branches, `master` and `1.0.0.x`, unless a branch is named.

### Which code do I run?

The fixes of 5 October 2026 are commits on the branch heads. To see whether your installation has one, look at the
file itself rather than at a version number:

```bash
grep -c "dev\." web/app.php                                  # 1.0.0.x: 0 = host switch removed
grep -c "ALWAYS_UNCACHED_PATHS" app/AppCache.php             # 1.0.0.x: 1 or more = new AppCache
grep -n "trusted_proxies" config/packages/framework.yaml config/packages/ezpublish.yaml 2>/dev/null
grep -c "APP_HTTP_CACHE" public/index.php                    # 1.1.0.x to 1.3.0.x: 1 or more = wired
```

A project created from a release tag can take the fixed files from the branch of its line; they are project files,
not vendor code (`git show origin/master:app/AppCache.php > app/AppCache.php`, and so on), and the commits are listed
in each section.

---

## 14.1 The layers at a glance

| Layer | What can go wrong | Section |
|---|---|---|
| Web server | Project root served instead of `public/` or `web/`; PHP executed from the upload directory; a shipped `.htaccess` that asks for a password file of another server | [14.2](#142-what-the-web-server-must-never-hand-out) |
| Configuration | Placeholder `APP_SECRET`, JWT passphrase, purge token; secrets committed | [14.3](#143-secrets) |
| Environment | `APP_ENV=dev` from the shipped `.env`; on 1.0.0.x releases a `Host` header that switches to `dev` | [14.4](#144-debug-mode-and-the-environment) |
| Administration | Default password, admin, Layouts editor, REST and GraphQL reachable from anywhere | [14.5](#145-the-administration-interfaces) |
| Proxies | Forwarded headers believed from anyone, or not believed from the real proxy | [14.6](#146-behind-a-proxy-trusted-proxies) |
| HTTP cache | Private pages marked public (1.0.0.x releases); purge open to anyone | [14.7](#147-http-cache-safety) |
| Browser | Session cookies without `Secure`; missing security headers | [14.8](#148-sessions-cookies-and-forms), [14.9](#149-security-headers-and-https) |
| Files | Executable uploads; writable code | [14.10](#1410-uploads-and-the-storage-directory) |
| Server | Velocity configuration, symbolic links, forwarded protocol, panel password | [14.11](#1411-exponential-velocity) |

## 14.2 What the web server must never hand out

- **The document root is `public/`** on 1.1.0.x to 1.3.0.x and **`web/`** on 1.0.0.x, never the project root. The
  project root holds `.env` and `.env.local` (database and mail credentials, `APP_SECRET`), `.env.php` on 1.0.0.x,
  `config/` or `app/config/`, `config/jwt/*.pem` (the REST API's private key), `var/` (logs, sessions, cache, an
  SQLite database) and `vendor/`. A web server pointed at the project root gives all of that away. Test it:

  ```bash
  curl -s -o /dev/null -w '%{http_code}\n' https://www.example.com/.env          # must not be 200
  curl -s -o /dev/null -w '%{http_code}\n' https://www.example.com/composer.json # must not be 200
  ```

- **No PHP from the storage directory.** The shipped rules refuse script extensions below `var/`:
  `RewriteRule ^var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ - [F]` in the 1.0.0.x `.htaccess` files and
  `location ~ ^/var/.*(?i)\.(php3?|phar|phtml|sh|exe|pl|bin)$ { return 403; }` in
  [doc/nginx/media-site.conf](../nginx/media-site.conf). Keep them when you write your own configuration
  ([chapter 6](06-serving-the-site.md)).
- **Only the front controller runs.** Requests for any other `.php` file must not reach PHP. On 1.0.0.x
  `web/app_dev.php` exists; it refuses requests that carry `X-Forwarded-For` or `Client-IP` or do not come from
  `127.0.0.1`/`::1`, and `web/app.php` answers `400` to URLs that name the front controller itself. Delete
  `app_dev.php` on production servers anyway: a local reverse proxy that does not add `X-Forwarded-For` would pass its
  check, and so would Exponential Velocity, which hands PHP the visitor's address only when it trusts the proxy.
- **No directory listings**, no `.git`: the 1.0.0.x `.htaccess` sets `Options -Indexes` and redirects `.git` and
  `.svn` paths. Do the same in nginx and Velocity configurations, and deploy without the `.git` directory where you
  can.
- **The shipped `.htaccess` of the `1.0.0.x` branch asks for a password.** `web/.htaccess` is a link to
  `src/AppBundle/Resources/symlink/root_dev/.htaccess`; on the `1.0.0.x` branch (not on `master`) that file turns on
  HTTP Basic authentication (`AuthType Basic`, `Require valid-user`) with an `AuthUserFile` on the project's own demo
  server. On any other Apache server every request then fails (the password file does not exist). Comment the four
  lines out, or point `AuthUserFile` at a file of your own if a password in front of a staging site is what you want.
- **Reject unknown host names.** Give the site its own virtual host with exact `ServerName`/`server_name` entries and
  make the default virtual host answer `403` or close the connection. Several things in these lines decide by host
  name (the siteaccess `Map\Host` matcher, the 1.0.0.x `AppCache` lists, and on 1.0.0.x releases the environment,
  [14.4](#144-debug-mode-and-the-environment)); a server that accepts any `Host` header lets a visitor choose. Symfony
  can check it too, with `framework.trusted_hosts` (the 1.0.0.x `app/config/config.yml` has `trusted_hosts: ~`; on the
  later lines add the key under `framework:`):

  ```yaml
  framework:
      trusted_hosts: ['^www\.example\.com$', '^admin\.example\.com$']
  ```

  A request with another `Host` then gets a 400 ("Untrusted Host") instead of a page:

  ```bash
  curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: evil.example' https://www.example.com/   # 400 or the default vhost's 403
  ```

## 14.3 Secrets

What each line ships, and what you must replace:

| Secret | 1.0.0.x | 1.1.0.x, 1.2.0.x | 1.3.0.x |
|---|---|---|---|
| Framework secret | `env(SYMFONY_SECRET): ThisEzPlatformTokenIsNotSoSecret_PleaseChangeIt` in `app/config/parameters.yml.dist` | `APP_SECRET=ThisTokenIsNotSoSecretChangeIt` in `.env` | `APP_SECRET=` (empty) in `.env` |
| JWT key passphrase | not used | `JWT_PASSPHRASE=ThisTokenIsNotSoSecretChangeIt` | same |
| Varnish purge token | `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` (unset; `varnish_invalidate_token` in `app/config/default_parameters.yml`) | `HTTPCACHE_VARNISH_INVALIDATE_TOKEN=` (empty) | same |
| Database credentials | `parameters.yml` | `DATABASE_URL` (the `.env` example is a placeholder) | same |
| Mail, reCAPTCHA, Sentry, MailerLite | `parameters.yml` | `MAILER_DSN`, `GOOGLE_RECAPTCHA_*`, `SENTRY_DSN`, `MAILER_LITE_API_KEY` | same |

The framework secret signs CSRF tokens, remember-me cookies, signed URLs and fragment URIs. A placeholder that is
public in a repository is as good as no secret: anyone can forge what it signs. Generate one per installation:

```bash
openssl rand -hex 32
```

Where to put secrets:

- **1.0.0.x**: in `app/config/parameters.yml` (ignored by Git; `parameters.yml.dist` is the template), in `.env.php`
  (ignored by Git on the branches since 5 October 2026, commit `4f0ea4964` on `master` and `4246d1244` on `1.0.0.x`;
  check your own `.gitignore` if you started from a release), or as real environment variables of the PHP-FPM pool
  (`env[SYMFONY_SECRET] = ...`), which the `env(...)` parameters read.
- **1.1.0.x and later**: in `.env.local`, in the real environment, or in the Symfony secrets vault. `.env.local`,
  `.env.*.local`, `.env.local.php` and the production decryption key are ignored by Git on 1.2.0.x and 1.3.0.x, and
  on the `1.1.0.x` branch since 5 October 2026 (commit `56b5ad53b`); a project created from `v1.1.0.7` or older does
  not ignore them, so add the lines yourself:

  ```gitignore
  /.env.local
  /.env.local.php
  /.env.*.local
  /config/secrets/prod/prod.decrypt.private.php
  ```

  The vault:

  ```bash
  php bin/console secrets:generate-keys --env=prod
  php bin/console secrets:set APP_SECRET --random --env=prod
  php bin/console secrets:set DATABASE_URL --env=prod
  ```

  Never commit `config/secrets/prod/prod.decrypt.private.php`. Deploy it separately, or give the server
  `SYMFONY_DECRYPTION_SECRET` instead.
- For production speed, `composer dump-env prod` compiles the `.env` files into `.env.local.php`; that file holds the
  secrets in clear text, so treat it like `.env.local`.

Check before every commit that nothing secret is staged:

```bash
git status --short --ignored | grep -E '\.env|parameters\.yml|\.pem|decrypt'   # these must show as ignored (!!)
```

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
environment in `.env.local` or in the PHP-FPM pool (or the environment Velocity is started with):

```bash
APP_ENV=prod
APP_DEBUG=0
```

Check both what the console sees and what the web sees:

```bash
php bin/console about --env=prod | grep -E 'Environment|Debug'   # Environment  prod / Debug  false
curl -s -o /dev/null -w '%{http_code}\n' https://www.example.com/_profiler    # must not be 200
```

**1.0.0.x.** `web/app.php` takes the environment from `SYMFONY_ENV` (set by the server or by `.env.php`, default
`prod`; the shipped `.htaccess` sets `prod`) and debugging from `SYMFONY_DEBUG` (default on only in `dev`). The
console is different: `bin/console` falls back to **`dev`** when neither `--env` nor `SYMFONY_ENV` is given, so pass
`--env=prod` or export `SYMFONY_ENV=prod` for every command on a production server.

Up to the releases `v2.5.0.6` and `1.0.0.10` (and every `1.0.0.x` branch tag), `web/app.php` also contains a project
patch that runs before the environment is read:

```php
// PATCH JAC -  example.com =>  dev.example.com or example-dev.com  => enabled DEV mode
if ( str_contains( $_SERVER['HTTP_HOST'], 'dev.' ) ) {
    putenv( 'SYMFONY_ENV=dev' );
}
```

Any request whose `Host` header contains `dev.` (also `mydev.example.com`, or a forged header sent to the server's
address) runs in the `dev` environment with debugging on, if the web server lets that host name reach the site. The
block was removed from both branches on 5 October 2026 (commit `dddc937de` on `master`, `4598c1942` on `1.0.0.x`). If
`grep -n "dev\." web/app.php` still finds it in your installation, delete the five lines, or take `web/app.php` from
the branch; until then make sure the virtual host accepts only your exact production names
([14.2](#142-what-the-web-server-must-never-hand-out)).

`web/app.php` and `bin/console` also load `.env.php` from the project root when that file exists, so it can
`putenv()` values. Keep it out of the document root (it is, in the project root) and out of Git.

**PHP itself**, on every line: `display_errors=Off` and `display_startup_errors=Off` in production,
`log_errors=On`, and `zend.exception_ignore_args=On`, so stack traces in logs do not carry argument values such as
passwords.

What can go wrong: a site that "works on the console but not on the web" (or the reverse) usually runs in two
environments. Compare `php bin/console about` with the environment the web request uses ([13.1](13-troubleshooting.md#131-first-steps-for-any-problem)).

## 14.5 The administration interfaces

What each line exposes:

| Line | Interfaces | How they are reached in the shipped configuration |
|---|---|---|
| 1.0.0.x | Symfony admin (`admin`), Netgen admin UI (`ngadminui`), legacy admin (`legacy_admin`), Layouts (`/nglayouts/`), REST, GraphQL | `ngadminui` by URI `/ngadminui` (`app/config/ngadminui.yml`); `admin` and `legacy_admin` by host name (`app/config/ezplatform_siteaccess.yml`) |
| 1.1.0.x, 1.2.0.x | `adminui`, `ngadminui`, `legacy_admin`, Layouts, REST, GraphQL | by URI: `/adminui/`, `/ngadminui/`, `/legacy_admin/` |
| 1.3.0.x | `adminui`, Layouts, REST, GraphQL | by URI: `/adminui/` |

Steps:

1. **Change the administrator's password** at the first sign-in. The demo data of every line installs a user `admin`
   whose password is published in this repository's README. Remove or disable demo users you do not need.
2. **Replace the demo host map** of 1.0.0.x (`Map\Host` in `app/config/ezplatform_siteaccess.yml` and the
   `uncached_hostnames` list in `app/config/http_cache.yml`) with your own host names. As shipped, they name the
   project's own demo servers.
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

   Behind a proxy, `allow` sees the proxy's address unless nginx's `real_ip` module is configured with the same
   trusted list as the application ([14.6](#146-behind-a-proxy-trusted-proxies)); otherwise the rule lets everyone
   in or nobody.
4. **Netgen Layouts** lives under `/nglayouts` (`netgen_layouts.route_prefix`). Access is decided by repository
   policies of the module `nglayouts`: `nglayouts/admin`, `nglayouts/editor` and `nglayouts/api` stand for
   `ROLE_NGLAYOUTS_ADMIN`, `ROLE_NGLAYOUTS_EDITOR` and `ROLE_NGLAYOUTS_API`. Give editors only `editor` and `api`;
   keep `admin` (layout types, rules, shared layouts) for a few people.
5. **REST and GraphQL** (`/api/ezp/v2/` up to 1.1.0.x, `/api/ibexa/v2/` on 1.2.0.x and 1.3.0.x, and `/graphql`) answer
   with the permissions of the signed-in user, but they accept requests from anywhere. If no external client uses
   them, restrict them like the admin; if one does, use JWT (1.1.0.x and later) and the CORS setting
   `CORS_ALLOW_ORIGIN` (the shipped value allows only `localhost`).
6. **Roles**: keep the anonymous role to `content/read` on the public sections and `user/login` on the public
   siteaccesses. Review every role after importing demo data; the demo grants are for a demonstration.

## 14.6 Behind a proxy: trusted proxies

A TLS terminator, Varnish, a load balancer or a CDN in front of the site tells the application what the visitor asked
for in `X-Forwarded-*` headers. A visitor can send the same headers. The application must believe them only from the
proxies you name, and must believe them from those, or it builds `http://` URLs, mis-detects the client address, and
caches under the wrong scheme. Nexus has up to three places that decide this: the Symfony side, the legacy kernel and
Exponential Velocity. Configure every one that is in the request path, with the same list.

### The Symfony side

**1.0.0.x (Symfony 3.4).** `web/app.php` reads `SYMFONY_TRUSTED_PROXIES` (a comma-separated list) and calls
`Request::setTrustedProxies(..., Request::HEADER_X_FORWARDED_ALL)`. `HEADER_X_FORWARDED_ALL` includes
`X-Forwarded-Host`, so a trusted proxy must set or remove that header itself rather than pass on what the visitor
sent. The special value `TRUST_REMOTE` trusts whatever address connects, which is only safe when nothing but the proxy
can reach the PHP server. Set it in the PHP-FPM pool or the virtual host:

```apache
SetEnv SYMFONY_TRUSTED_PROXIES "127.0.0.1"
```

**1.1.0.x, 1.2.0.x and 1.3.0.x, branch heads.** Since 5 October 2026 the framework configuration reads the
`TRUSTED_PROXIES` variable that `.env` and the virtual host examples set (commit `98453e0d2` on `1.1.0.x`, in
`config/packages/ezpublish.yaml`; `c330cd215` on `1.2.0.x` and `4eb6eb6dd` on `1.3.0.x`, in
`config/packages/framework.yaml`):

```yaml
framework:
    trusted_proxies: '%env(default::TRUSTED_PROXIES)%'
    trusted_headers: ['x-forwarded-for', 'x-forwarded-proto', 'x-forwarded-port']
```

The value is a comma-separated list of addresses and CIDR ranges; unset or empty trusts no proxy. The shipped `.env`
says `TRUSTED_PROXIES=127.0.0.1`, so a proxy on the same machine (Varnish, nginx in front of Apache) is trusted out of
the box. Set your own list in `.env.local` or the server environment:

```dotenv
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/16
```

`X-Forwarded-Host` stays untrusted on purpose: trusting it lets a client that reaches the proxy choose the host name
the application uses for absolute URLs and for siteaccess matching.

On 1.3.0.x the explicit setting replaces Symfony 7.4's default, which reads `SYMFONY_TRUSTED_PROXIES` and
`SYMFONY_TRUSTED_HEADERS` (`%env(default::SYMFONY_TRUSTED_PROXIES)%`, as `config/reference.php` lists it). On the
branch head those two variables therefore have no effect; use `TRUSTED_PROXIES`.

**Releases `v1.1.0.7`, `v1.2.0.0` and `1.3.0.5` and older.** Nothing reads `TRUSTED_PROXIES`: the front controller
`public/index.php` uses the Symfony Runtime, and no configuration file names the variable. Add the block above
yourself (on 1.1.0.x the `framework:` block is in `config/packages/ezpublish.yaml`, on the later lines in
`config/packages/framework.yaml`). On `1.3.0.5` `SYMFONY_TRUSTED_PROXIES` works without any change, because the
framework default reads it.

Notes for every line:

- List only the addresses or ranges of your proxies (`127.0.0.1`, `10.0.0.0/8`, the published ranges of a CDN).
- The proxy must overwrite the forwarded headers, not append to what the visitor sent: in Varnish, set
  `X-Forwarded-Proto` in `vcl_recv` rather than passing it on; in nginx, `proxy_set_header X-Forwarded-Proto $scheme`.
- Check it. Sent directly to the application server by a client that is not a proxy, a forged header must change
  nothing. The admin login redirect shows the scheme the application believes:

  ```bash
  curl -sI -H 'X-Forwarded-Proto: https' http://app-server.internal/adminui/ | grep -i '^location'
  # expected: Location: http://app-server.internal/adminui/login   (the header was ignored)
  ```

  The same request through your proxy over HTTPS must give an `https://` location. On 1.0.0.x use `/ngadminui/`.

### The legacy kernel (1.0.0.x to 1.2.0.x)

The legacy kernel reads the forwarded headers itself (for HTTPS detection, the host name, the client address of
`DebugByIP`, `TrustedIPList` and the audit log). From Exponential 6.0.15 it believes them only from the proxies listed
in `site.ini [HTTPHeaderSettings] TrustedProxies[]` (default: `127.0.0.1` and `::1`), and reads `X-Forwarded-For` from
the right. On 5 October 2026 this is on the `main` branch of `se7enxweb/exponential` (commit `490e602301`); the newest
release tag is `v6.0.14`, which does not have it. Which one a Nexus line installs depends on its constraint:
1.2.0.x locks `se7enxweb/exponential dev-main` and gets the change with the next `composer update` of that package;
1.0.0.x and 1.1.0.x require `^6.0.12` and, with `prefer-stable`, stay on `v6.0.14` until 6.0.15 is tagged. Check what
is installed:

```bash
grep -n "TrustedProxies" ezpublish_legacy/settings/site.ini    # no output: the installed kernel predates it
```

Set the list in your legacy override settings (kept in the project and linked into `ezpublish_legacy/settings/`):

```ini
[HTTPHeaderSettings]
TrustedProxies[]
TrustedProxies[]=127.0.0.1
TrustedProxies[]=10.0.0.0/16
ClientIpByCustomHTTPHeader=X-Forwarded-For
```

The first, empty `TrustedProxies[]` line replaces the default list instead of adding to it. The whole behaviour, with
examples for Apache, nginx, cloud load balancers and CDNs, is in
[Forwarded headers are trusted only from configured proxies](https://github.com/se7enxweb/exponential/blob/main/doc/bc/6.0/trusted-proxies.md).
Keep the Symfony list and the legacy list the same.

### Exponential Velocity

When Exponential Velocity serves the site, it works out the visitor's address itself from its own list of trusted
proxies, `Q.webserver.proxy.trusted` (default `127.0.0.1` and `::1`), and passes that address to PHP as
`REMOTE_ADDR`. X-Forwarded-For is read from the right: trusted proxies are skipped and the first other address is the
visitor.

The protocol follows the same list since Velocity `v0.0.4.44` (tagged 5 October 2026; engine commit `380a64d`,
"Forwarded protocol headers are read only from trusted proxies"): `HTTPS` and `REQUEST_SCHEME` follow
`X-Forwarded-Proto` (or the header named in `Q.webserver.proxy.headers.proto`; of a comma-separated value the first
entry counts), `CloudFront-Forwarded-Proto` and Cloudflare's `CF-Visitor` only when the connection comes from a
trusted proxy, and a TLS connection is HTTPS from any client. Up to `v0.0.4.43` any client on a plain HTTP listener
could send `X-Forwarded-Proto: https` and PHP saw `HTTPS=on`: the application then built `https://` URLs and set
`Secure` cookies for a plain connection. Update to `v0.0.4.44` or newer; until you can, make sure the plain listener
is reachable only by your proxy (bind it to `127.0.0.1`) or only redirects to HTTPS. Check the version you run:

```bash
php /opt/exponential-velocity/sbin/qbixserver.php --version     # or: qbixserver --version from the packages
```

One consequence of the fix: a proxy that sets the protocol header must now be listed in `Q.webserver.proxy.trusted`,
which is loopback only unless configured. A TLS terminator on another machine that worked before without being listed
makes PHP see plain HTTP after the update, which can show as a redirect loop on a site that forces HTTPS; add its
address.

Configure proxies in front of Velocity in a configuration snippet, for example
`/etc/vc/conf-available/reverse-proxy.conf`, enabled with `qbixctl enconf reverse-proxy` (the file name is yours; the
engine ships none):

```json
{ "Q": { "webserver": { "proxy": {
    "trusted": ["127.0.0.1", "::1", "10.0.0.0/16"],
    "headers": { "ip": "X-Forwarded-For", "proto": "X-Forwarded-Proto" }
} } } }
```

Let the proxies pass the original `Host` header. Because Velocity already hands PHP the visitor's address and the
right `HTTPS` value, the Symfony list does not need to contain anything for Velocity itself; keep `TRUSTED_PROXIES` to
what really connects to PHP.

## 14.7 HTTP cache safety

### The 1.0.0.x AppCache

On 1.0.0.x the Symfony reverse proxy is on outside `dev` (`SYMFONY_HTTP_CACHE` unset), and the class used is the
project's `app/AppCache.php` (loaded through the `classmap` in `composer.json`). It extends the platform's proxy and,
after the response is built, may change its `Cache-Control` header. What it does depends on the code you have.

**Branch heads since 5 October 2026** (commit `2a9c202b9` on `master`, `3fb8765dc` on `1.0.0.x`). The class never
makes a private or personal response public. It leaves a response alone when any of these holds:

- its `Cache-Control` is `private` (Symfony sends every response that is not declared public as private) or
  `no-store`, or it carries no `no-cache` at all;
- the response sets a cookie or carries `X-User-Context-Hash`;
- the request carries an `Authorization` header or a session cookie (`eZSESSID*`, `IBX_SESSION_ID*`, `PHPSESSID*`,
  `is_logged_in`);
- the host is in `http_cache.uncached_hostnames` of `app/config/http_cache.yml` or in the comma-separated environment
  variable `HTTP_CACHE_UNCACHED_HOSTNAMES`;
- the siteaccess found for the host through the `Map\Host` list of `app/config/ezplatform_siteaccess.yml` is
  `ngadminui`, `admin`, `legacy_admin` or one listed in `http_cache.uncached_siteaccesses`;
- the path matches `http_cache.uncached_url_patterns` or one of the built-in patterns, which cover `/admin`,
  `/ngadminui`, `/legacy_admin`, `/nglayouts`, `/graphql`, the Content Browser (`/cb`), `/api/`, login, logout,
  register and user pages and `/_` paths, also behind a URI siteaccess prefix (`/en/graphql`).

Only a response that is already public and also says `no-cache`, answering an anonymous request, is turned into
`public, max-age=3600, s-maxage=3600, must-revalidate`; a public response with `s-maxage` only loses the stray
`no-cache`. The settings are read once per PHP process from `app/config/`; under persistent workers (Velocity),
reload after editing `http_cache.yml`.

**Releases up to `v2.5.0.6` and `1.0.0.10`, and the `1.0.0.x` branch tags.** The older class **replaces** every
`Cache-Control` that contains `private` or `no-cache` without both `public` and `s-maxage` by
`public, max-age=3600, s-maxage=3600, must-revalidate`, unless the host, siteaccess or path is excluded or the response
sets a cookie or a user context hash (read from the code). It looks for its settings in `config/` instead of
`app/config/`, so it never reads `app/config/http_cache.yml` and always uses a list of demo host names built into the
class: editing `http_cache.yml` on a release changes nothing. A page that the platform marked private, because it
depends on the signed-in user or carries a form token, then leaves the server as public for an hour; the Symfony proxy
decides what to store before the rewrite, but the browser, and any Varnish, CDN or company proxy in front, sees the
public header. On such an installation:

1. Take `app/AppCache.php` from the branch of your line (`git show origin/master:app/AppCache.php > app/AppCache.php`,
   or `origin/1.0.0.x`; the two are identical), then `php bin/console cache:clear --env=prod`.
2. Or switch the class off with `SYMFONY_HTTP_CACHE=0` and use Varnish, or no proxy, instead.

On both, before go-live:

1. Replace the demo host names in `uncached_hostnames` with your own admin and editor host names (or set
   `HTTP_CACHE_UNCACHED_HOSTNAMES`), and keep `uncached_siteaccesses` in step with your siteaccess names.
2. Add the paths of your own forms and member pages to `uncached_url_patterns`.
3. Test with a signed-in browser session: sign in, open a page that greets the user or carries a form, and read its
   `Cache-Control` response header in the browser's developer tools. It must say `private` (or `no-cache`, `no-store`),
   never `public` with an `s-maxage`. For an anonymous request, a public header on a content page is expected:

   ```bash
   curl -sI https://www.example.com/ | grep -i '^cache-control'     # anonymous visitor
   ```

### 1.1.0.x to 1.3.0.x: the Symfony proxy

These lines use the platform's own `AppCache` (`EzSystems\PlatformHttpCacheBundle\AppCache` on 1.1.0.x,
`Ibexa\Bundle\HttpCache\AppCache` on 1.2.0.x and 1.3.0.x), which does not rewrite cache headers. On the branch heads
since 5 October 2026 `public/index.php` wraps the kernel in it when `APP_HTTP_CACHE` is true (commits `7f8d95f29`,
`c5e6aca42` and `ac3f43a74`); the releases ignore the variable and run without a proxy. Turn it on only when no
Varnish or other caching proxy sits in front ([10.4](10-operations.md#104-http-cache-and-purging)).

### On every line

- **Varnish purges must be authenticated.** The shipped VCL parameters (`doc/varnish/vcl/parameters.vcl` on 1.0.0.x)
  allow purges from `127.0.0.1` and `192.168.0.0/16` (`acl invalidators`); narrow that to your application servers.
  Where an IP ACL is not possible, set `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` (every line) to a random value and the
  same value in the VCL.
- **User context.** Pages vary by the user context hash (`X-User-Context-Hash`); never strip the `Vary` header in the
  proxy, or users with different rights share cached pages.
- **Velocity's response cache** is a third cache layer; it is off unless configured, and must skip the platform's
  session cookies when on ([10.4.6](10-operations.md#1046-velocitys-response-cache)).
- **Compression of responses with secrets.** The vendor stopped compressing REST and JSON responses in 3.3.41 and
  4.6.14 because of BREACH. The shipped nginx example compresses only static types and notes the risk for
  `text/html`; the 1.0.0.x `.htaccess` deflates `text/html` and `application/json`. Do not compress responses that
  contain both a secret (CSRF token) and attacker-controlled input over TLS, or pad them.
- **Purge after permission changes.** Role and section changes do not change cached pages; purge the cache.

## 14.8 Sessions, cookies and forms

- **Secure cookies.** 1.2.0.x sets `session.cookie_secure: auto` and `cookie_samesite: lax` in
  `config/packages/framework.yaml`; `auto` sets `Secure` when the request is HTTPS, which depends on trusted proxies
  being right ([14.6](#146-behind-a-proxy-trusted-proxies)). On 1.0.0.x `app/config/config.yml` sets
  `cookie_httponly: true`; add `cookie_secure: true` when the site is HTTPS only. 1.3.0.x has only `session: true`, and
  1.1.0.x `session: ~` in `config/packages/ezpublish.yaml`; add the keys under `framework.session` there:

  ```yaml
  framework:
      session:
          cookie_secure: auto
          cookie_samesite: lax
          cookie_httponly: true
  ```

- **Session storage.** 1.1.0.x and later store sessions in `var/sessions/<env>/` (`SESSION_SAVE_PATH`); the directory
  must not be readable by other system users. With several application servers use a shared handler (Redis), as
  [chapter 10](10-operations.md) describes.
- **CSRF.** The login form of 1.0.0.x uses the `authenticate` token (`app/config/security.yml`); 1.2.0.x enables
  `csrf_protection: true`. Keep CSRF protection on for your own forms.
- **Brute force.** The lines ship no login throttling of their own. Limit `/login` (and the legacy login of
  `legacy_admin`) at the web server or with Symfony's login throttling (`login_throttling` in the firewall, available
  on Symfony 5.2 and later, which needs `symfony/rate-limiter`).

Check the cookie a login sets:

```bash
curl -sI https://www.example.com/adminui/login | grep -i '^set-cookie'   # expect: secure; httponly; samesite=lax
```

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
`root_dev` variant, which sets `SYMFONY_ENV=prod` but no headers (and on the `1.0.0.x` branch asks for a password,
[14.2](#142-what-the-web-server-must-never-hand-out)). Point the link at `root_prod`, or set the headers in the virtual
host. For Velocity, see [14.11](#1411-exponential-velocity).

Redirect HTTP to HTTPS for every host, admin first. Check the result:

```bash
curl -sI https://www.example.com/ | grep -i -E '^(strict-transport|x-content-type|x-frame|referrer-policy|content-security)'
```

## 14.10 Uploads and the storage directory

- Uploaded files land below `public/var/<site>/storage/` (`web/var/...`, a link to the legacy
  `ezpublish_legacy/var/...`, on 1.0.0.x). The web server serves images from there directly; keep the script rule of
  [14.2](#142-what-the-web-server-must-never-hand-out).
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

- **Nothing outside the document root.** Since `v0.0.4.25` (23 September 2026) Velocity refuses, with 403, any
  static file or script whose real path, after every symbolic link is followed, lies outside the document root
  (`Q_WebServer::insideRoot()`, checked at every place a request is turned into a file). A link that stays inside the
  root still works. `Q.webserver.followSymlinks` (default `false`) restores the old behaviour of following links
  wherever they lead. Nexus relies on links that leave the root, so out of the box these answer 403 under Velocity:

  | Line | Links that leave the document root |
  |---|---|
  | all | `public/bundles/*` (`web/bundles/*` on 1.0.0.x): `assets:install --symlink --relative` links them into `vendor/` and `src/`; on 1.0.0.x the links are committed to Git |
  | 1.0.0.x to 1.2.0.x | `web/design`, `web/extension`, `web/share` and `web/var` (on 1.1.0.x and 1.2.0.x in `public/`): `ezpublish:legacy:assets_install --symlink` links them into `ezpublish_legacy/`; every image of the legacy storage is served through `var` |
  | 1.0.0.x | `ezpublish_legacy/var/site/storage` itself, linked to `src/AppBundle/ezpublish_legacy/var/site/storage` ([doc/INSTALL.md](../INSTALL.md)) |
  | all | files that `ngsite:symlink:project` links from `assets/symlink/` (or `src/AppBundle/Resources/symlink/`) into the root, such as `robots.txt` on 1.0.0.x |

  The way out depends on the line:

  - **1.3.0.x** (no legacy kernel): install bundle assets as copies and keep storage a real directory under
    `public/var/`. Re-run the copy after every `composer install` or `update`, because the Composer `auto-scripts`
    put the links back:

    ```bash
    php bin/console assets:install public --env=prod     # no --symlink: hard copies
    find public -type l                                  # what is still a link (only links inside public/ are fine)
    ```

  - **1.0.0.x to 1.2.0.x**: the legacy kernel's design, extension and storage directories are served through links
    by design, and copying `var/` would split the storage in two. Turn link following on for that site, and keep the
    static-file pattern of the site file as narrow as [chapter 6](06-serving-the-site.md) shows it, so that only
    assets, bundle files and storage images are served from disk:

    ```json
    { "Q": { "webserver": { "followSymlinks": true } } }
    ```

    With it, any link under the document root serves its target, so nothing that writes into the document root
    (uploads, a deploy tool) may create links there. The setting is read once per server process; restart Velocity
    after changing it.

  To find the links before the first request, run `find public web -maxdepth 3 -type l 2>/dev/null` in the project
  root.
- **Forwarded headers.** The visitor's address, and since `v0.0.4.44` (engine commit `380a64d`) the protocol,
  are taken from forwarded headers only when the connection comes from `Q.webserver.proxy.trusted`
  ([14.6](#146-behind-a-proxy-trusted-proxies)). On `v0.0.4.43` and older, keep the plain HTTP listener away from
  visitors or let it only redirect.
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
- **The control panel** changes the server; its password must meet strict rules (16 or more characters, four
  character classes) and its credential store must belong to root or the server's user. Set the password before the
  server is reachable, and keep the panel off the public interface.
- **Persistent workers.** A worker that keeps the application in memory keeps its secrets and container there too;
  after changing `.env.local`, secrets or configuration, restart the workers, not only `cache:clear`.

The Velocity documents: [security](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/security.md)
(symbolic link containment), [headers](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/headers.md)
and [passwords](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/passwords.md); the Exponential
book's [hardening chapter](https://github.com/se7enxweb/exponential/blob/main/doc/install/13-security-hardening.md)
has a section on Velocity-specific hardening for the legacy kernel.

## 14.12 Keeping up to date and reporting problems

- Follow the releases of your line ([chapter 11.9](11-upgrading-between-lines.md#119-patch-updates-inside-a-line)).
  The platform, Symfony and Netgen packages are forks maintained by se7enxweb; their fixes arrive through
  `composer update` within the line's constraints. Project files (`web/app.php`, `app/AppCache.php`,
  `public/index.php`, `config/`) are yours once the project is created: a fix to one of them reaches your site only
  when you merge the new tag or copy the file, as the sections above show.
- Check dependencies for published advisories:

  ```bash
  composer audit          # Composer 2.4 and later
  ```

  The 1.0.0.x Composer scripts also run `bin/security-checker security:check`.
- Report vulnerabilities privately to the address in [SECURITY.md](../../SECURITY.md), not in a public issue.

## 14.13 Go-live checklist

- [ ] Document root is `public/` (`web/` on 1.0.0.x); `.env*`, `config/`, `var/`, `vendor/` are not reachable.
- [ ] Scripts below `var/` refused; `app_dev.php` removed (1.0.0.x); no directory listings; the `1.0.0.x` branch's
      Basic authentication lines removed or pointed at your own password file.
- [ ] Unknown host names refused by the web server; `trusted_hosts` set.
- [ ] `APP_SECRET` (`SYMFONY_SECRET` on 1.0.0.x), `JWT_PASSPHRASE`, purge token and database password replaced; none
      of them in Git; `.gitignore` covers `.env.local`, `.env.*.local`, `.env.local.php` (and `.env.php` on 1.0.0.x).
- [ ] `APP_ENV=prod`, `APP_DEBUG=0`; `/_profiler` not reachable; on 1.0.0.x the `dev.` host patch absent from
      `web/app.php` and console commands run with `--env=prod`.
- [ ] `display_errors=Off`, `zend.exception_ignore_args=On`.
- [ ] Administrator password changed; demo users removed; roles reviewed.
- [ ] Demo host maps replaced (1.0.0.x); admin, Layouts, REST and GraphQL restricted to who needs them.
- [ ] Trusted proxies set on the Symfony side (`TRUSTED_PROXIES` read by your `framework` configuration), in the
      legacy kernel (1.0.0.x to 1.2.0.x) and in Velocity, and tested with a forged header.
- [ ] 1.0.0.x: `app/AppCache.php` is the fixed class; its host lists name your hosts; a signed-in page is not public.
- [ ] 1.1.0.x and later: `APP_HTTP_CACHE` on only when no Varnish is in front, and only with a front controller that
      reads it.
- [ ] Varnish purges limited to the application servers; token set.
- [ ] Session cookies `Secure` and `HttpOnly`; HTTPS redirect for every host; security headers set.
- [ ] Code not writable by the web server; uploads limited.
- [ ] Velocity: symbolic link behaviour decided (bundle assets copied, or `followSymlinks` on for the legacy lines),
      forwarded protocol safe (`v0.0.4.44` or newer, or no plain listener for visitors), headers configured, panel
      password set.
- [ ] `composer audit` clean or reviewed.

## 14.14 References

In this repository (read per branch with `git show <branch>:<path>`):

- `web/app.php`, `web/app_dev.php`, `bin/console`, `app/AppCache.php`, `app/config/http_cache.yml`,
  `app/config/config.yml`, `app/config/security.yml`, `app/config/parameters.yml.dist`,
  `src/AppBundle/Resources/symlink/root_dev/.htaccess`, `src/AppBundle/Resources/symlink/root_prod/.htaccess`,
  `.gitignore` (1.0.0.x, `master`)
- `.env`, `.gitignore`, `public/index.php`, `config/bundles.php`, `config/packages/framework.yaml`,
  `config/packages/ezpublish.yaml` (1.1.0.x), `config/reference.php` (1.3.0.x)
- The commits of 5 October 2026: `dddc937de`, `2a9c202b9`, `4f0ea4964` (`master`); `4598c1942`, `3fb8765dc`,
  `4246d1244` (`1.0.0.x`); `98453e0d2`, `7f8d95f29`, `56b5ad53b` (`1.1.0.x`); `c330cd215`, `c5e6aca42` (`1.2.0.x`);
  `4eb6eb6dd`, `ac3f43a74` (`1.3.0.x`)
- [doc/apache2](../apache2/), [doc/nginx](../nginx/), [doc/varnish](../varnish/) (1.0.0.x)
- [SECURITY.md](../../SECURITY.md)

Exponential:

- [Forwarded headers are trusted only from configured proxies](https://github.com/se7enxweb/exponential/blob/main/doc/bc/6.0/trusted-proxies.md)
- [Exponential book, chapter 13: Security hardening](https://github.com/se7enxweb/exponential/blob/main/doc/install/13-security-hardening.md)
- Exponential Velocity: [security](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/security.md),
  [headers](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/headers.md),
  [passwords](https://github.com/se7enxweb/exponential-velocity/blob/main/docs/passwords.md),
  [commit 380a64d](https://github.com/se7enxweb/exponential-velocity/commit/380a64d) (forwarded protocol),
  [CHANGELOG, v0.0.4.44](https://github.com/se7enxweb/exponential-velocity/blob/main/CHANGELOG.md)

Symfony and PHP:

- [Configuring Symfony to work behind a load balancer or a reverse proxy](https://symfony.com/doc/current/deployment/proxies.html)
  (current), [5.x](https://symfony.com/doc/5.x/deployment/proxies.html), [3.x](https://symfony.com/doc/3.x/deployment/proxies.html)
- [framework.trusted_proxies](https://symfony.com/doc/current/reference/configuration/framework.html#trusted-proxies),
  [framework.secret](https://symfony.com/doc/current/reference/configuration/framework.html#secret),
  [secrets management](https://symfony.com/doc/current/configuration/secrets.html)
- [Security: login throttling](https://symfony.com/doc/current/security.html#limiting-login-attempts)
- [PHP: zend.exception_ignore_args](https://www.php.net/manual/en/ini.core.php#ini.zend.exception-ignore-args)

Platform:

- [Security checklist](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/security/security_checklist/)
- [Reverse proxy](https://doc.ibexa.co/en/latest/infrastructure_and_maintenance/cache/http_cache/reverse_proxy/)
