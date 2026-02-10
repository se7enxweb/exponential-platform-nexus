<?php

namespace Cjw\PublishToolsBundle\Controller;

use Cjw\PublishToolsBundle\Classes\CjwPublishToolsWeather;
use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

class AsyncWeatherController extends Controller implements ContainerAwareInterface
{
    /**
        * Main action for viewing content through a location in the repository.
        * Response will be cached with HttpCache validation model (Etag)
        *
        * it is possible to change the ttl of the cache by setting a global Variable
        *
        * e.g. in twig template with a twig function which set the global variable
        *
        *  $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 0; => disable cache
        *  $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 3; => 3 s
        *
        * @param int $locationId
        * @param string $viewType
        * @param boolean $layout
        * @param array $params
        *
        * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
        * @throws \Exception
        *
        * @return \Symfony\Component\HttpFoundation\Response
        */
    /**
     * @var ContainerInterface|null
     */
    protected $container;


    protected function getContainer () {

        return $this->container;
    }



    public function getCurrentAirTempAction (Request $request, $ttl = 1800, $locationId = 2) {
        $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 300; //=> 3 s
        if (!$request->isXmlHttpRequest())
        {
            return new JsonResponse(array(
                'status' => 'Error',
                'message' => 'Error'),
                400);
        }

//            $url = $request->request->get('url');
            $weather = new CjwPublishToolsWeather();

                    if($this->getContainer()->hasParameter('ApiUrl')){
                        $apiUrl = $this->getContainer()->getParameter( 'ApiUrl' );

                            $result = $weather->getCurrentAirTemp( $apiUrl, $this->getContainer() );

                    }






        $response = new JsonResponse(array("response" => $result));

//            $ttl = 10;
//            // damit bei contentcache löschen der cache hier auch gelöscht wird
//            $locationId = 2;


        if ( $ttl == 0 )
        {
            $response->setPrivate();
            //$response->setMaxAge( $ttl );

        }
        elseif ( $ttl > 0 )
        {
            $response->setPublic();
//                $response->setMaxAge( $ttl );
            $response->setSharedMaxAge( $ttl );
        }
        if ( $locationId > 0 )
        {
            $response->headers->set( 'X-Location-Id', (int) $locationId );
        }
        if(session_status() == PHP_SESSION_NONE){
            session_cache_limiter('public');
        }
        // Set Cache-Control header
        header('Cache-Control: max-age=1800');



        return $response;

        return new JsonResponse(array(
            'status' => 'Error',
            'message' => 'Error'),
            400);
    }
//    public function getCurrentWaterTempAction ($api_mode=1 ,$url=1, $bsh_water_id=140130) {
//        $weather = new CjwPublishToolsWeather();
//        $result = $weather->htmlRequest($url, $bsh_water_id);
//
//        return new JsonResponse(array( "response" => $result ));
//    }

    public function getWeatherAction ( Request $request , $ttl = 1800, $locationId = 2 ) {
        $GLOBALS['EZ_TWIG_HTTP_CACHE_TTL'] = 300; //=> 3 s
        if (!$request->isXmlHttpRequest())
        {
            return new JsonResponse(array(
                'status' => 'Error',
                'message' => 'Error'),
                400);
        }
        if(session_status() == PHP_SESSION_NONE){
            session_cache_limiter('public');
        }
        $result = '';
        // Set Cache-Control header
        header('Cache-Control: max-age=1800');

//            $bsh_water_id = (int)$request->request->get('bsh_water_id');
//            $url = $request->request->get('weather_url');
            $weather = new CjwPublishToolsWeather();
//            $apiMode = $this->getConfigResolver()->getParameter( 'GetWeatherWith' );
            if($this->getContainer()->hasParameter('GetWeatherWith')){
                $apiMode = $this->getContainer()->getParameter('GetWeatherWith');
                if ($apiMode == 'ApiRequest'){
                    $apiMode = 2;
                    if($this->getContainer()->hasParameter('ApiUrl')){
                        $apiUrl = $this->getContainer()->getParameter( 'ApiUrl' );
                        if($this->getContainer()->hasParameter('BSHWaterId')){
                            $bshWaterId = $this->getContainer()->getParameter( 'BSHWaterId' );
                            $result = $weather->apiRequest($apiMode, $apiUrl, $bshWaterId, $this->getContainer());
                        }
                    }



                }
                else{
                    if($this->getContainer()->hasParameter('ApiUrl')){
                        $apiUrl = $this->getContainer()->getParameter( 'ApiUrl' );
                        if($this->getContainer()->hasParameter('BSHWaterId')){
                            $bshWaterId = $this->getContainer()->getParameter( 'BSHWaterId' );
                            $result = $weather->htmlRequest($apiUrl, $bshWaterId, $this->getContainer());
                        }
                    }
                }


            $response = new JsonResponse(array("response" => $result));

//            $ttl = 10;
//            // damit bei contentcache löschen der cache hier auch gelöscht wird
//            $locationId = 2;


            if ( $ttl == 0 )
            {
                $response->setPrivate();
                //$response->setMaxAge( $ttl );

            }
            elseif ( $ttl > 0 )
            {
                $response->setPublic();
//                $response->setMaxAge( $ttl );
                $response->setSharedMaxAge( $ttl );
            }
            if ( $locationId > 0 )
            {
                $response->headers->set( 'X-Location-Id', (int) $locationId );
            }




            return $response;
        }
        return new JsonResponse(array(
            'status' => 'Error',
            'message' => 'Error'),
            400);
    }



}
