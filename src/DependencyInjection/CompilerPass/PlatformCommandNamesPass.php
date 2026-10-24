<?php

declare(strict_types=1);

namespace App\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Keeps exponential:install and exponential:reindex for this project's own commands.
 *
 * se7enxweb/ezplatform-kernel 1.3.43 and later name the Platform's install and reindex commands
 * exponential:install and exponential:reindex (ibexa:* and ezplatform:* stay as aliases). The
 * console registers the bundles' commands after the project's, so the Platform's took over those
 * names: "bin/console exponential:install exponential-media" ran the Platform's installer and
 * stopped with "Unknown install type". The Platform's two commands go back to their ibexa:* names
 * here; on a kernel that never renamed them this changes nothing.
 */
class PlatformCommandNamesPass implements CompilerPassInterface
{
    private const COMMANDS = [
        'EzSystems\PlatformInstallerBundle\Command\InstallPlatformCommand' => ['ibexa:install', ['ezplatform:install']],
        'eZ\Bundle\EzPublishCoreBundle\Command\ReindexCommand' => ['ibexa:reindex', ['ezplatform:reindex']],
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::COMMANDS as $id => [$name, $aliases]) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $definition->addMethodCall('setName', [$name]);
            $definition->addMethodCall('setAliases', [$aliases]);

            // A lazy command is found by the name in its tag, before it is constructed.
            $tags = $definition->getTag('console.command');
            if ($tags !== []) {
                $definition->clearTag('console.command');
                foreach ($tags as $attributes) {
                    if (isset($attributes['command'])) {
                        $attributes['command'] = $name;
                    }
                    $definition->addTag('console.command', $attributes);
                }
            }
        }
    }
}
