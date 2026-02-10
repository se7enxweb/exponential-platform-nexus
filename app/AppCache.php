<?php

use EzSystems\PlatformHttpCacheBundle\AppCache as PlatformHttpCacheBundleAppCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AppCache.
 *
 * Extended to fix cache headers using ezPlatform SiteAccess detection.
 * This class:
 * 1. Parses ezplatform_siteaccess.yml to extract hostname→siteaccess mapping
 * 2. Detects the current SiteAccess from the request hostname
 * 3. Checks if the SiteAccess is configured to be uncached (from http_cache.yml)
 * 4. Post-processes cache headers AFTER all kernel.response listeners
 * 
 * Configuration files read directly (not through DI container):
 * - app/config/ezplatform_siteaccess.yml: SiteAccess hostname mapping
 * - app/config/http_cache.yml: HTTP cache exclusion rules
 *   - http_cache.uncached_hostnames: which hostnames never cache
 *   - http_cache.uncached_siteaccesses: which siteaccesses never cache
 *   - http_cache.uncached_url_patterns: URL patterns that never cache
 * 
 * This is the proper ezPlatform extension point for HTTP cache processing.
 */
class AppCache extends PlatformHttpCacheBundleAppCache
{
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

        // Post-process: fix restrictive cache headers from FOSHttpCache
        $this->fixCacheHeaders($request, $response);

        return $response;
    }

    /**
     * Fix cache headers set to no-cache/private by FOSHttpCache
     * CRITICAL: Remove conflicting no-cache directive that breaks Varnish caching
     */
    private function fixCacheHeaders(Request $request, Response $response): void
    {
        $cacheControl = $response->headers->get('Cache-Control', '');

        if (empty($cacheControl)) {
            return;
        }

        // Check if this hostname should never be cached (first priority check)
        if ($this->isUncachedHostname($request)) {
            return;
        }
        
        // Check if this siteaccess should never be cached (based on YAML config)
        if ($this->isUncachedSiteaccess($request)) {
            return;
        }
        
        $path = $request->getPathInfo();
        
        // Check if path matches uncached patterns (from YAML config)
        if ($this->isUncachedPath($path)) {
            return;
        }
        
        // Do NOT override if response has Set-Cookie (session-dependent)
        if ($response->headers->has('Set-Cookie')) {
            return;
        }
        
        // Do NOT override if response has X-User-Context-Hash (user-specific)
        if ($response->headers->has('X-User-Context-Hash')) {
            return;
        }

        // Check for problematic cache directives
        $hasNoCache = strpos($cacheControl, 'no-cache') !== false;
        $hasPrivate = strpos($cacheControl, 'private') !== false;
        $hasPublic = strpos($cacheControl, 'public') !== false;
        $hasSMaxAge = strpos($cacheControl, 's-maxage') !== false;
        $hasMaxAge = strpos($cacheControl, 'max-age') !== false;

        // CRITICAL FIX: If response has BOTH 'no-cache' AND 'public/s-maxage', 
        // remove the conflicting 'no-cache' directive
        // no-cache prevents proxy caching even when public is set - MUST remove it
        if ($hasNoCache && $hasPublic && $hasSMaxAge) {
            // Remove conflicting no-cache while preserving public cache settings
            $newCacheControl = str_replace('no-cache, ', '', $cacheControl);
            $newCacheControl = str_replace('no-cache', '', $newCacheControl);
            // Clean up any double commas or spaces
            $newCacheControl = preg_replace('/,\s+,/', ',', $newCacheControl);
            $newCacheControl = trim(trim($newCacheControl, ','));
            
            if (!empty($newCacheControl)) {
                $response->headers->set('Cache-Control', $newCacheControl);
                $response->headers->remove('Pragma');
                $response->headers->remove('Expires');
            }
            return;
        }

        // If we have restrictive headers but NOT proper public caching configured
        if (($hasNoCache || $hasPrivate) && (!$hasPublic || !$hasSMaxAge)) {
            // Replace with correct public cache header for shared caching
            $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600, must-revalidate');
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
        }
    }
    
    /**
     * Check if current request's SiteAccess should never be cached
     * Detects SiteAccess from hostname using ezPlatform configuration
     */
    private function isUncachedSiteaccess(Request $request): bool
    {
        // Load configuration if not already loaded
        if (empty($this->uncachedSiteaccesses) && empty($this->siteaccessHostMapping)) {
            $this->loadConfigFromYaml();
        }
        
        // Detect which siteaccess this hostname belongs to
        $detectedSiteaccess = $this->detectSiteaccessFromHostname($request->getHost());
        
        if ($detectedSiteaccess === null) {
            // Could not detect siteaccess, play it safe - don't cache
            return false;
        }
        
        // Check if this siteaccess is in the uncached list
        return in_array($detectedSiteaccess, $this->uncachedSiteaccesses, true);
    }
    
    /**
     * Check if current request's hostname should never be cached
     * Checks against list of uncached hostnames from http_cache.yml
     */
    private function isUncachedHostname(Request $request): bool
    {
        // Load configuration if not already loaded
        if (empty($this->uncachedHostnames)) {
            $this->loadConfigFromYaml();
        }
        
        $hostname = $request->getHost();
        
        // Check if this hostname is in the uncached list
        return in_array($hostname, $this->uncachedHostnames, true);
    }
    
    /**
     * Detect SiteAccess from hostname using ezPlatform configuration
     * 
     * This manually matches the hostname against the siteaccess mapping
     * without relying on full kernel initialization
     */
    private function detectSiteaccessFromHostname(string $host): ?string
    {
        // Load configuration if not already loaded
        if (empty($this->siteaccessHostMapping)) {
            $this->loadConfigFromYaml();
        }
        
        // Direct hostname match (from Map\Host matcher in YAML)
        if (isset($this->siteaccessHostMapping[$host])) {
            return $this->siteaccessHostMapping[$host];
        }
        
        // No match found - return null
        return null;
    }
    
    /**
     * Check if path matches uncached patterns
     * Reads from ezpublish.http_cache.uncached_url_patterns in ezplatform_siteaccess.yml
     */
    private function isUncachedPath(string $path): bool
    {
        // Load uncached paths from config if not already loaded
        if (empty($this->uncachedPaths)) {
            $this->loadConfigFromYaml();
        }
        
        foreach ($this->uncachedPaths as $pattern) {
            if (preg_match("~{$pattern}~", $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Load configuration from YAML files
     * Parses ezplatform_siteaccess.yml to build siteaccess mappings and cache settings
     */
    private function loadConfigFromYaml(): void
    {
        // Try to load from YAML config files
        $this->loadSiteaccessMappingFromConfig();
        $this->loadUncachedHostnamesFromConfig();
        $this->loadUncachedSiteaccessesFromConfig();
        $this->loadUncachedPathsFromConfig();
    }
    
    /**
     * Load siteaccess to hostname mapping from ezplatform_siteaccess.yml
     * Extracts the Match\Host section from ezpublish.siteaccess.match
     */
    private function loadSiteaccessMappingFromConfig(): void
    {
        // Parse ezplatform_siteaccess.yml to extract Map\Host matcher
        // The siteaccess → hostname mapping is used to detect which siteaccess handles requests
        // Located in: ezpublish.siteaccess.match.Map\Host
        $siteaccessConfigPath = $this->getConfigPath('ezplatform_siteaccess.yml');
        
        if (!file_exists($siteaccessConfigPath)) {
            // Fallback to sensible defaults if file not found
            $this->siteaccessHostMapping = [
                'nga.platform.demo.cjw.se7enx.com' => 'ngadminui',
            ];
            return;
        }
        
        try {
            $config = Yaml::parseFile($siteaccessConfigPath);
            
            // Extract Map\Host matcher from ezpublish.siteaccess.match
            if (isset($config['ezpublish']['siteaccess']['match']['Map\Host'])) {
                $this->siteaccessHostMapping = $config['ezpublish']['siteaccess']['match']['Map\Host'];
            } else {
                // Fallback to defaults if Map\Host not configured
                $this->siteaccessHostMapping = [
                    'nga.platform.demo.cjw.se7enx.com' => 'ngadminui',
                ];
            }
        } catch (\Exception $e) {
            // Fallback to sensible defaults on YAML parse error
            $this->siteaccessHostMapping = [
                'nga.platform.demo.cjw.se7enx.com' => 'ngadminui',
            ];
        }
    }
    
    /**
     * Load uncached hostnames from http_cache.uncached_hostnames
     * Defined in app/config/http_cache.yml (separate from ezplatform configuration)
     */
    private function loadUncachedHostnamesFromConfig(): void
    {
        // Load from YAML config file: http_cache.yml
        // These hostnames will NEVER be publicly cached
        
        $httpCacheConfigPath = $this->getConfigPath('http_cache.yml');
        
        if (!file_exists($httpCacheConfigPath)) {
            // Fallback: at minimum, exclude admin-related hostnames
            $this->uncachedHostnames = [
                'nga.platform.cjw.alpha.se7enx.com',
                'edit.platform.cjw.alpha.se7enx.com',
                'editor.platform.cjw.alpha.se7enx.com',
            ];
            return;
        }
        
        try {
            $config = Yaml::parseFile($httpCacheConfigPath);
            
            // Extract uncached_hostnames from http_cache.uncached_hostnames
            if (isset($config['http_cache']['uncached_hostnames'])) {
                $this->uncachedHostnames = $config['http_cache']['uncached_hostnames'];
            } else {
                // Fallback if configuration not found
                $this->uncachedHostnames = [
                    'nga.platform.cjw.alpha.se7enx.com',
                    'edit.platform.cjw.alpha.se7enx.com',
                    'editor.platform.cjw.alpha.se7enx.com',
                ];
            }
        } catch (\Exception $e) {
            // Fallback on YAML parse error
            $this->uncachedHostnames = [
                'nga.platform.cjw.alpha.se7enx.com',
                'edit.platform.cjw.alpha.se7enx.com',
                'editor.platform.cjw.alpha.se7enx.com',
            ];
        }
    }
    
    /**
     * Load uncached siteaccesses from http_cache.uncached_siteaccesses
     * Defined in app/config/http_cache.yml (separate from ezplatform configuration)
     */
    private function loadUncachedSiteaccessesFromConfig(): void
    {
        // Load from YAML config file: http_cache.yml
        // These siteaccesses will NEVER be publicly cached
        
        $httpCacheConfigPath = $this->getConfigPath('http_cache.yml');
        
        if (!file_exists($httpCacheConfigPath)) {
            // Fallback: at minimum, exclude admin siteaccesses
            $this->uncachedSiteaccesses = ['ngadminui', 'admin', 'legacy_admin'];
            return;
        }
        
        try {
            $config = Yaml::parseFile($httpCacheConfigPath);
            
            // Extract uncached_siteaccesses from http_cache.uncached_siteaccesses
            if (isset($config['http_cache']['uncached_siteaccesses'])) {
                $this->uncachedSiteaccesses = $config['http_cache']['uncached_siteaccesses'];
            } else {
                // Fallback if configuration not found
                $this->uncachedSiteaccesses = ['ngadminui', 'admin', 'legacy_admin'];
            }
        } catch (\Exception $e) {
            // Fallback on YAML parse error
            $this->uncachedSiteaccesses = ['ngadminui', 'admin', 'legacy_admin'];
        }
    }
    
    /**
     * Load uncached paths from http_cache.uncached_url_patterns
     * Defined in app/config/http_cache.yml (separate from ezplatform configuration)
     */
    private function loadUncachedPathsFromConfig(): void
    {
        // Load from YAML config file: http_cache.yml
        // These URL patterns should never be cached
        
        $httpCacheConfigPath = $this->getConfigPath('http_cache.yml');
        
        if (!file_exists($httpCacheConfigPath)) {
            // Fallback to default patterns
            $this->uncachedPaths = [
                '^/admin',           // Admin interface
                '^/api/',            // API endpoints (user-specific)
                '^/login',           // Login/auth pages
                '^/logout',          // Logout
                '^/user',            // User pages
                '^/_',               // System routes
                '^/ngadminui',       // eZ Platform admin UI
                '^/ng/',             // Admin/internal routes
            ];
            return;
        }
        
        try {
            $config = Yaml::parseFile($httpCacheConfigPath);
            
            // Extract uncached_url_patterns from http_cache.uncached_url_patterns
            if (isset($config['http_cache']['uncached_url_patterns'])) {
                $this->uncachedPaths = $config['http_cache']['uncached_url_patterns'];
            } else {
                // Fallback if configuration not found
                $this->uncachedPaths = [
                    '^/admin',           // Admin interface
                    '^/api/',            // API endpoints (user-specific)
                    '^/login',           // Login/auth pages
                    '^/logout',          // Logout
                    '^/user',            // User pages
                    '^/_',               // System routes
                    '^/ngadminui',       // eZ Platform admin UI
                    '^/ng/',             // Admin/internal routes
                ];
            }
        } catch (\Exception $e) {
            // Fallback on YAML parse error
            $this->uncachedPaths = [
                '^/admin',           // Admin interface
                '^/api/',            // API endpoints (user-specific)
                '^/login',           // Login/auth pages
                '^/logout',          // Logout
                '^/user',            // User pages
                '^/_',               // System routes
                '^/ngadminui',       // eZ Platform admin UI
                '^/ng/',             // Admin/internal routes
            ];
        }
    }
    
    /**
     * Get config file path relative to app/config
     */
    private function getConfigPath(string $filename): string
    {
        // Get the app directory (parent of the directory containing AppCache.php)
        $appDir = dirname(dirname(__FILE__));
        return $appDir . '/config/' . $filename;
    }
}



