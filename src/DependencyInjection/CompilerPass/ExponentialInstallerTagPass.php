<?php

/**
 * @copyright Copyright (C) Exponential Platform Contributors. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace App\DependencyInjection\CompilerPass;

use App\RepositoryInstaller\Command\ExponentialInstallCommand;
use Ibexa\Bundle\RepositoryInstaller\DependencyInjection\Compiler\InstallerTagPass;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects services tagged as "ibexa.installer" into
 * ExponentialInstallCommand::$installers, mirroring what
 * InstallerTagPass does for ibexa:install.
 */
final class ExponentialInstallerTagPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ExponentialInstallCommand::class)) {
            return;
        }

        $commandDef = $container->findDefinition(ExponentialInstallCommand::class);
        $installers = [];

        foreach ($container->findTaggedServiceIds(InstallerTagPass::INSTALLER_TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['type'])) {
                    throw new LogicException(
                        \sprintf(
                            'Service tag %s needs a "type" attribute to identify the installer. You need to provide a tag for %s.',
                            InstallerTagPass::INSTALLER_TAG,
                            $id,
                        ),
                    );
                }

                $installers[$tag['type']] = new Reference($id);
            }
        }

        $commandDef->replaceArgument('$installers', $installers);
    }
}
