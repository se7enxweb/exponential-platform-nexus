<?php

/**
 * Cache Header Fixer Service
 * 
 * This service hooks into the kernel to apply final cache header fixes
 * without requiring changes to the web/app.php front controller.
 * 
 * It uses a clever trick: we register a kernel.response listener at compile time
 * that modifies how the Response object handles headers.
 */

namespace AppBundle\Service;

use Symfony\Component\HttpFoundation\Response;

class CacheHeaderFixer
{
    /**
     * Apply cache header fixes to a response
     * 
     * This is the core logic that ensures public caching headers are set.
     */
    public static function fixResponse(Response $response)
    {
        $currentCC = $response->headers->get('Cache-Control', '');
        
        if (!empty($currentCC)) {
            // If header has public and s-maxage already set, it's correct
            if (strpos($currentCC, 'public') !== false && strpos($currentCC, 's-maxage') !== false) {
                // Good - public cache is enabled
                return;
            }
            
            // If it has no-cache or no-store but should be public - fix it
            if (strpos($currentCC, 'no-cache') !== false || strpos($currentCC, 'no-store') !== false) {
                // Remove and completely reset the header
                $response->headers->remove('Cache-Control');
                $response->headers->set('Cache-Control', 'public, max-age=3600, s-maxage=3600, must-revalidate');
                
                // Also ensure Pragma and Expires don't override
                $response->headers->remove('Pragma');
                $response->headers->remove('Expires');
            }
        }
    }
}
