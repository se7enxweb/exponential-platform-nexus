<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;

// todo: get available languages

class SitemapsController extends Controller
{
    public function sitemapAction( Request $request )
    {
        $ttl = 3600 * 6;


        // use cache with http_cache
        $response = new Response;
        $response->headers->set( 'Content-Type', 'text/xml' );
        $response->setPublic();
        $response->setSharedMaxAge( $ttl );

//        $siteaccessName = $this->container->get( 'ezpublish.siteaccess' )->name;
//        $cacheFile = $this->container->getParameter( 'kernel.cache_dir' ) . '/sitemap_'.$siteaccessName.'.xml';
//
//
//
//        $cacheFileMtime = 0;
//        if ( file_exists( $cacheFile ) )
//        {
////            $cacheFileMtime = stat( $cacheFile )['mtime'];
//            $cacheFileMtime = stat( $cacheFile );
//            $cacheFileMtime = $cacheFileMtime['mtime'];
//        }
//
//        if ( $cacheFileMtime < ( time() - $ttl ) )
//        {
            $publishToolsService = $this->get( 'cjw_publishtools.service.functions' );

            $rootLocationId = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );

            $include = array();
            if( $this->getConfigResolver()->hasParameter( 'include', 'sitemaps' ) )
            {
                $include = $this->getConfigResolver()->getParameter( 'include', 'sitemaps' );
            }

            $listLocations = $publishToolsService->fetchLocationListArr(
                array( $rootLocationId ), array( 'depth' => 10,
                                  'limit' => 25000,
                                  'include' => $include,
                                  'main_location_only' => true,
                                  'datamap' => false )
            );

            $urls = array();

            foreach( $listLocations[$rootLocationId]['children'] as $location )
            {
                $loc = $this->generateUrl( $location );
                if( $location->contentInfo->modificationDate->getTimestamp() > 1000 )
                {
                    $lastmod = date( 'c', $location->contentInfo->modificationDate->getTimestamp() );
                }
                else
                {
                    $lastmod = date( 'c', time() );
                }

                $urls[] = array( 'loc' => $loc, 'lastmod' => $lastmod );
            }


//            $hostname = $this->getRequest()->getHost();
            $hostname = $request->getHost();

            $protocol = 'https';
            if( $this->getConfigResolver()->hasParameter( 'protocol', 'sitemaps' ) )
            {
                $protocol = $this->getConfigResolver()->getParameter( 'protocol', 'sitemaps' );
            }

            $sitemapXmlResponse = $this->render(
                'CjwPublishToolsBundle::sitemap.xml.twig',
                array( 'urls' => $urls, 'hostname' => $hostname, 'protocol' => $protocol ),
                $response
            );

//        }
//        else
//        {
//            $sitemapXmlResponse = $response->setContent( file_get_contents( $cacheFile ) );
//        }

        return $sitemapXmlResponse;
    }

    public function robotsAction( Request $request )
    {
        return $this->render(
            'CjwPublishToolsBundle::robots.txt.twig',
            array( 'hostname' => $request->getHost() )
        );
    }
}
