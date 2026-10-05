<?php

use App\Kernel;
use EzSystems\PlatformHttpCacheBundle\AppCache;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);

    // APP_HTTP_CACHE=1 puts Symfony's HTTP cache proxy (AppCache) in front of the kernel.
    // Off by default; leave it off when Varnish or another reverse proxy does the caching.
    if (filter_var($context['APP_HTTP_CACHE'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
        Request::enableHttpMethodParameterOverride();

        return new AppCache($kernel);
    }

    return $kernel;
};
