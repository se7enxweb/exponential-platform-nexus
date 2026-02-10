<?php

namespace Cjw\TmvBundle\DependencyInjection;

use Jean85\PrettyVersions;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TmvExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/config');

        $loader = new DelegatingLoader(
            new LoaderResolver(
                [
                    new GlobFileLoader($container, $locator),
                    new YamlFileLoader($container, $locator),
                ]
            )
        );

        //$loader->load('services/**/*.yaml', 'glob');
        $loader->load('services.yml');
        /*$loader->load('block_definitions.yml');
        $loader->load('block_type_groups.yml');
        $loader->load('block_types.yml');
        $loader->load('block_view.yml');*/
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->setParameter(
            'netgen_layouts.standard.asset_version',
            PrettyVersions::getVersion('netgen/layouts-standard')->getShortCommitHash()
        );

        $prependConfigs = [
            'block_definitions.yml' => 'netgen_layouts',
            'block_type_groups.yml' => 'netgen_layouts',
            'block_types.yml'       => 'netgen_layouts',
            'block_view.yml'        => 'netgen_layouts'
        ];

        foreach ($prependConfigs as $configFile => $prependConfig) {
            $configFile = __DIR__ . '/../Resources/config/' . $configFile;
            $config = Yaml::parse((string) file_get_contents($configFile));
            $container->prependExtensionConfig($prependConfig, $config);
            $container->addResource(new FileResource($configFile));
        }

        $configFile = __DIR__ . '/../Resources/config/content_view.yml';
        $config = Yaml::parse(file_get_contents($configFile));
        $container->prependExtensionConfig('ezpublish', ['system' => $config]);
        $container->addResource(new FileResource($configFile));
    }
}
