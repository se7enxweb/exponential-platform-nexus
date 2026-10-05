eZ Platform Varnish configuration
=================================

Prerequisites
-------------
* A working Varnish 5.1 or higher _(6.0 is a LTS, so the recommended version we test against)_.
* [Varnish xkey module](https://github.com/varnish/varnish-modules/)

Recommended VCL base files
--------------------------
This directory ships a VCL for this branch: [vcl/varnish4_xkey.vcl](vcl/varnish4_xkey.vcl) (despite its name, its
header says it is for Varnish 5.0 or higher with the xkey vmod) and [vcl/parameters.vcl](vcl/parameters.vcl), which
it includes and which holds the backend and the `invalidators` and `debuggers` ACLs. Adjust `parameters.vcl` to your
backend address and narrow the ACLs to your application servers before use.

The HTTP cache package of this branch, `se7enxweb/ezplatform-http-cache` (a fork of
[ezsystems/ezplatform-http-cache](https://github.com/ezsystems/ezplatform-http-cache/tree/1.0/docs/varnish)), also
ships its VCL files in `vendor/se7enxweb/ezplatform-http-cache/docs/varnish/vcl/` (`varnish4.vcl`, `varnish5.vcl`,
`parameters.vcl`). If a dependency also pulls in the upstream `ezsystems/ezplatform-http-cache`, its VCL files are in
`vendor/ezsystems/ezplatform-http-cache/docs/varnish/vcl/`; `composer show | grep http-cache` tells you what is
installed. Take the VCL from the package version you run, not from a newer one.

Pointing the application at Varnish
-----------------------------------
On this branch (Symfony 3.4, front controller `web/app.php`) set, in the PHP-FPM pool or the virtual host:

* `HTTPCACHE_PURGE_TYPE=varnish` and `HTTPCACHE_PURGE_SERVER=http://127.0.0.1:6081` (the address Varnish listens on);
  the purge type is read when the container is built, so clear the cache afterwards;
* `SYMFONY_HTTP_CACHE=0`, so that the built-in Symfony proxy (`app/AppCache.php`) does not cache in front of PHP as
  well;
* `SYMFONY_TRUSTED_PROXIES=127.0.0.1` (the address Varnish connects from), so that the client address and HTTPS are
  taken from Varnish's `X-Forwarded-*` headers;
* `HTTPCACHE_VARNISH_INVALIDATE_TOKEN` only where an IP ACL is not possible, with the same value in the VCL.

The book covers the HTTP cache in [chapter 10.4](../book/10-operations.md#104-http-cache-and-purging) and its safety
in [chapter 14.7](../book/14-security-hardening.md#147-http-cache-safety).


> **Note:** Http cache management is done with the help of [FOSHttpCacheBundle](https://foshttpcachebundle.readthedocs.io/en/latest/).
  One may need to tweak their VCL further on according to [FOSHttpCache documentation](https://foshttpcache.readthedocs.io/en/latest/varnish-configuration.html)
  in order to use features supported by it.
