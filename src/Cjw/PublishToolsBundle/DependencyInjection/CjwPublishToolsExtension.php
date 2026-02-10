<?php

namespace Cjw\PublishToolsBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CjwPublishToolsExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load( array $configs, ContainerBuilder $container )
    {
        $loader = new Loader\YamlFileLoader( $container, new FileLocator( __DIR__.'/../Resources/config' ) );
        $loader->load( 'services.yml');

        if ( $container->hasParameter('ezpublish.persistence.legacy.search.gateway.sort_clause_handler.common.field.class') )
        {
            $loader->load('storage_engines/legacy/search_query_handlers.yml' );
        }
        else
        {
            $loader->load('storage_engines/legacy/search_query_handlers_new_namespaces.yml' );
        }


        // return SiteApi Objects if NetgenSiteApiBundle is activated
        // @see vendor/netgen/ezplatform-site-api/bundle/Resources/config/default_settings.yml
        if ( $container->hasParameter('netgen_ez_platform_site_api.default.override_url_alias_view_action' ) )
        {
            $loader->load('services/publish_tools_service_use_netgen_siteapi_objects.yml' );
        }
        // default return ez location or content objects
        else
        {
            $loader->load('services/publish_tools_service_use_ez_objects.yml' );
        }







    }
}
