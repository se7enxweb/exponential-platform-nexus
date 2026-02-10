<?php

declare(strict_types=1);

/**
 * Custom AppCache extending EzSystems\PlatformHttpCacheBundle\AppCache
 * 
 * This class intercepts the HTTP cache processing to fix cache headers
 * that were set to restrictive values by FOSHttpCache listeners.
 * 
 * By placing this in AppBundle, it can be auto-discovered instead of the vendor version.
 */

use EzSystems\PlatformHttpCacheBundle\AppCache as BaseAppCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AppCache extends BaseAppCache
{
    /**
     * Override handle to post-process cache headers
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // Call parent to get the response through the normal cache flow
        $response = parent::handle($request, $type, $catch);

        // Post-process: fix restrictive cache headers from FOSHttpCache
        $this->fixCacheHeaders($response);

        return $response;
    }

    /**
     * Fix cache headers that were incorrectly set to no-cache/private
     */
    private function fixCacheHeaders(Response $response): void
    {
        $cacheControl = $response->headers->get('Cache-Control', '');

        // Only modify if we detect the problematic pattern
        if (!empty($cacheControl)) {
            $hasNoCache = strpos($cacheControl, 'no-cache') !== false;
            $hasPrivate = strpos($cacheControl, 'private') !== false;
            $hasPublic = strpos($cacheControl, 'public') !== false;
            $hasSMaxAge = strpos($cacheControl, 's-maxage') !== false;

            // If it has restrictive headers but not proper public caching
            if (($hasNoCache || $hasPrivate) && (!$hasPublic || !$hasSMaxAge)) {
                // Set correct public cache header
                $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600, must-revalidate');
                $response->headers->remove('Pragma');
                $response->headers->remove('Expires');
            }
        }
    }
}
