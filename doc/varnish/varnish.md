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


> **Note:** Http cache management is done with the help of [FOSHttpCacheBundle](http://foshttpcachebundle.readthedocs.org/).
  One may need to tweak their VCL further on according to [FOSHttpCache documentation](http://foshttpcache.readthedocs.org/en/latest/varnish-configuration.html)
  in order to use features supported by it.
