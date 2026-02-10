<?php

declare(strict_types=1);

namespace AppBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Final cache header cleanup that runs absolutely last in the response cycle.
 * Uses an approach to strip problematic directives added by other listeners.
 */
final class FinalCacheHeaderCleanupSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', PHP_INT_MIN],
        ];
    }

    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();
        $cacheControl = $response->headers->get('Cache-Control', '');
        
        // Log what we're seeing
        error_log("[CACHE DEBUG] Before: " . $cacheControl);
        
        // Only process if we detect problematic cache headers
        if (!empty($cacheControl) && (strpos($cacheControl, 'no-cache') !== false || strpos($cacheControl, 'private') !== false)) {
            // Build the correct cache header
            $correctHeader = 'public, max-age=3600, s-maxage=3600, must-revalidate';
            
            // Set it with maximum priority (multiple times to override)
            $response->headers->set('Cache-Control', $correctHeader);
            $response->headers->remove('Pragma');
            $response->headers->remove('Expires');
            
            error_log("[CACHE DEBUG] After: " . $response->headers->get('Cache-Control', ''));
        }
    }
}
