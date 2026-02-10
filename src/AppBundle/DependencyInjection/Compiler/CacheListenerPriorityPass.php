<?php

namespace AppBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler Pass to ensure CacheControlHeaderListener runs AFTER all other kernel.response listeners.
 */
class CacheListenerPriorityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Ensure our cache control listener runs at absolute lowest priority
        if ($container->hasDefinition('app.event_listener.cache_control_header_listener')) {
            $definition = $container->getDefinition('app.event_listener.cache_control_header_listener');
            $definition->clearTag('kernel.event_subscriber');
            $definition->addTag('kernel.event_subscriber', ['priority' => PHP_INT_MIN]);
        }
    }
}
