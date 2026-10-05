<?php

use EzSystems\PlatformHttpCacheBundle\AppCache as PlatformHttpCacheBundleAppCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AppCache.
 *
 * Extends the platform's Symfony HTTP cache proxy to turn a "public, no-cache" response of an
 * anonymous request into a shared-cacheable one (public, s-maxage=3600).
 *
 * A response is NEVER made public when any of the following holds:
 * - its Cache-Control is private (Symfony sends every response that is not declared public as
 *   private) or no-store; a public response with s-maxage only loses a stray no-cache;
 * - it sets a cookie or carries X-User-Context-Hash;
 * - the request carries an Authorization header or a session cookie
 *   (eZSESSID*, IBX_SESSION_ID*, PHPSESSID*, is_logged_in);
 * - the host is listed as uncached (http_cache.uncached_hostnames in app/config/http_cache.yml,
 *   plus the comma separated HTTP_CACHE_UNCACHED_HOSTNAMES environment variable; empty by default);
 * - the host maps to an uncached siteaccess (http_cache.uncached_siteaccesses, matched through
 *   ezpublish.siteaccess.match.Map\Host in app/config/ezplatform_siteaccess.yml);
 * - the path matches http_cache.uncached_url_patterns or one of ALWAYS_UNCACHED_PATHS, which
 *   cover the admin siteaccesses, Netgen Layouts, the content browser and GraphQL whatever
 *   the configuration says.
 */
class AppCache extends PlatformHttpCacheBundleAppCache
{
    /**
     * Paths that are never made public, independent of http_cache.yml.
     * An optional first segment covers URI-matched siteaccesses (/en/graphql).
     */
    private const ALWAYS_UNCACHED_PATHS = [
        '^/admin(/|$)',
        '^/ngadminui(/|$)',
        '^/legacy_admin(/|$)',
        '^(/[^/]+)?/nglayouts(/|$)',
        '^(/[^/]+)?/graphql(/|$)',
        '^(/[^/]+)?/cb(/|$)',
        '^(/[^/]+)?/api/',
        '^(/[^/]+)?/(login|logout|register|user)(/|$)',
        '^/_',
    ];

    /** Siteaccesses that are never made public, independent of http_cache.yml. */
    private const ALWAYS_UNCACHED_SITEACCESSES = ['ngadminui', 'admin', 'legacy_admin'];

    /** Request cookies that mean a user session (or login) is in play. */
    private const SESSION_COOKIE_PREFIXES = ['eZSESSID', 'IBX_SESSION_ID', 'PHPSESSID', 'is_logged_in'];

    private $uncachedHostnames = [];
    private $uncachedSiteaccesses = [];
    private $uncachedPaths = [];
    private $siteaccessHostMapping = [];
    private $yamlLoaded = false;

    /**
     * Override handle() to fix cache headers after listeners
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // Execute parent (runs all kernel.response listeners)
        $response = parent::handle($request, $type, $catch);

        $this->fixCacheHeaders($request, $response);

        return $response;
    }

    /**
     * Make a "no-cache" response of an anonymous request shared-cacheable, or drop a
     * "no-cache" that contradicts an explicit "public, s-maxage". Never touches a response
     * that is private, personal or belongs to an excluded host, siteaccess or path.
     */
    private function fixCacheHeaders(Request $request, Response $response): void
    {
        $cacheControl = (string) $response->headers->get('Cache-Control', '');

        if ($cacheControl === '') {
            return;
        }

        // Directives of the Cache-Control header as it will be sent. Symfony marks every
        // response private unless it says public or s-maxage, and such a response is left
        // private here: only responses the application already declared public are touched.
        $headers = $response->headers;
        $hasNoCache = $headers->hasCacheControlDirective('no-cache');
        $hasPrivate = $headers->hasCacheControlDirective('private');
        $hasNoStore = $headers->hasCacheControlDirective('no-store');
        $hasPublic = $headers->hasCacheControlDirective('public');
        $hasSMaxAge = $headers->hasCacheControlDirective('s-maxage');

        if (!$hasNoCache || $hasPrivate || $hasNoStore) {
            return;
        }

        if ($this->isPersonal($request, $response)) {
            return;
        }

        $this->loadConfigFromYaml();

        if ($this->isUncachedHostname($request)
            || $this->isUncachedSiteaccess($request)
            || $this->isUncachedPath($request->getPathInfo())
        ) {
            return;
        }

        // Explicit "public, s-maxage" plus a stray "no-cache": drop the "no-cache" only.
        if ($hasPublic && $hasSMaxAge) {
            $newCacheControl = preg_replace('/(^|,)\s*no-cache\s*(?=,|$)/i', '', $cacheControl);
            $newCacheControl = trim(preg_replace('/\s*,\s*,\s*/', ', ', $newCacheControl), " ,");

            if ($newCacheControl !== '') {
                $response->headers->set('Cache-Control', $newCacheControl);
                $response->headers->remove('Pragma');
                $response->headers->remove('Expires');
            }

            return;
        }

        $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600, must-revalidate');
        $response->headers->remove('Pragma');
        $response->headers->remove('Expires');
    }

    /**
     * True when the request or the response is tied to a user.
     */
    private function isPersonal(Request $request, Response $response): bool
    {
        if ($response->headers->has('Set-Cookie') || $response->headers->getCookies() !== []) {
            return true;
        }

        if ($response->headers->has('X-User-Context-Hash')) {
            return true;
        }

        if ($request->headers->has('Authorization') || $request->server->has('PHP_AUTH_USER')) {
            return true;
        }

        foreach (array_keys($request->cookies->all()) as $cookieName) {
            foreach (self::SESSION_COOKIE_PREFIXES as $prefix) {
                if (strpos((string) $cookieName, $prefix) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if current request's SiteAccess should never be cached
     */
    private function isUncachedSiteaccess(Request $request): bool
    {
        $detectedSiteaccess = $this->siteaccessHostMapping[$request->getHost()] ?? null;

        if ($detectedSiteaccess === null) {
            return false;
        }

        return in_array($detectedSiteaccess, $this->uncachedSiteaccesses, true);
    }

    /**
     * Check if current request's hostname should never be cached
     */
    private function isUncachedHostname(Request $request): bool
    {
        return in_array(strtolower($request->getHost()), $this->uncachedHostnames, true);
    }

    /**
     * Check if path matches an uncached pattern
     */
    private function isUncachedPath(string $path): bool
    {
        foreach ($this->uncachedPaths as $pattern) {
            if (@preg_match('~' . str_replace('~', '\~', (string) $pattern) . '~', $path) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load the exclusions once per process: app/config/http_cache.yml, the
     * HTTP_CACHE_UNCACHED_HOSTNAMES environment variable and the built-in lists.
     */
    private function loadConfigFromYaml(): void
    {
        if ($this->yamlLoaded) {
            return;
        }

        $this->yamlLoaded = true;

        $httpCache = $this->readYaml('http_cache.yml')['http_cache'] ?? [];
        $siteaccessConfig = $this->readYaml('ezplatform_siteaccess.yml');

        $mapping = $siteaccessConfig['ezpublish']['siteaccess']['match']['Map\Host'] ?? [];
        $this->siteaccessHostMapping = is_array($mapping) ? $mapping : [];

        $hostnames = is_array($httpCache['uncached_hostnames'] ?? null) ? $httpCache['uncached_hostnames'] : [];
        $fromEnv = getenv('HTTP_CACHE_UNCACHED_HOSTNAMES');
        if (is_string($fromEnv) && $fromEnv !== '') {
            $hostnames = array_merge($hostnames, explode(',', $fromEnv));
        }
        $this->uncachedHostnames = array_values(array_filter(array_map(
            static function ($host) {
                return strtolower(trim((string) $host));
            },
            $hostnames
        )));

        $siteaccesses = is_array($httpCache['uncached_siteaccesses'] ?? null) ? $httpCache['uncached_siteaccesses'] : [];
        $this->uncachedSiteaccesses = array_values(array_unique(array_merge(self::ALWAYS_UNCACHED_SITEACCESSES, $siteaccesses)));

        $paths = is_array($httpCache['uncached_url_patterns'] ?? null) ? $httpCache['uncached_url_patterns'] : [];
        $this->uncachedPaths = array_values(array_unique(array_merge(self::ALWAYS_UNCACHED_PATHS, $paths)));
    }

    private function readYaml(string $filename): array
    {
        $path = dirname(__DIR__) . '/app/config/' . $filename;

        if (!is_file($path)) {
            return [];
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (\Exception $e) {
            return [];
        }

        return is_array($config) ? $config : [];
    }
}
