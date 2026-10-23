<?php

declare(strict_types=1);

namespace App\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Points the Netgen Layouts component view type provider at the Site API content view setting.
 *
 * netgen/layouts-ezplatform-site-api 1.4 configures the provider with "ngcontent_view", the
 * setting name of Site API 3. Site API 4 (se7enxweb/ezplatform-site-api 4.3) stores the views in
 * "ng_content_view", so every lookup failed with "Parameter 'ngcontent_view' ... could not be
 * found": component blocks (hero, about, features ...) could not list their view types in the
 * Layouts editor and failed to render with kernel.debug enabled.
 */
final class LayoutsSiteApiContentViewParameterPass implements CompilerPassInterface
{
    private const SERVICE_NAME = 'netgen_layouts.ezplatform.block.block_definition.config_provider.ezplatform';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(self::SERVICE_NAME)) {
            return;
        }

        $container->findDefinition(self::SERVICE_NAME)
            ->replaceArgument(3, 'ng_content_view');
    }
}
