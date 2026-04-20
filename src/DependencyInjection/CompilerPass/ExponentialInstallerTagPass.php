<?php

declare(strict_types=1);

namespace App\DependencyInjection\CompilerPass;

use App\Command\ExponentialInstallCommand;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Wires services tagged exponential.installer into ExponentialInstallCommand.
 */
class ExponentialInstallerTagPass implements CompilerPassInterface
{
    public const INSTALLER_TAG = 'exponential.installer';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ExponentialInstallCommand::class)) {
            return;
        }

        $installCommandDef = $container->findDefinition(ExponentialInstallCommand::class);
        $installers = [];

        foreach ($container->findTaggedServiceIds(self::INSTALLER_TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new LogicException(
                        sprintf(
                            'Service tag %s needs a "type" attribute to identify the installer. You need to provide a tag for %s.',
                            self::INSTALLER_TAG,
                            $id,
                        ),
                    );
                }

                $installers[$tag['type']] = new Reference($id);
            }
        }

        $installCommandDef->replaceArgument('$installers', $installers);
    }
}
