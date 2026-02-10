<?php
/*  Controller to output a static file through the symfony kernel, so you don't have add custom webserver rules

e.g. static index.html or favicon.ico
simple add one rule to you routing.yml and the file to output
- possible to add ttl und locaation id to controll http_cache

ttl = 0        => disable cache  default one day 86400
locationId > 0 => cache will be purged if cache is purged from backed for node xy


example routing.yml

# static file routing examples
cjwpublishtools_static_example_index_html:
    path: /index.html
    defaults:
      _controller: CjwPublishToolsBundle:StaticFile:output
      file: @CjwPublishToolsBundle/Resources/static/index.html
      ttl: 60
      locationId: 2

cjwpublishtools_static_example_test_xml:
    path: /test.xml
    defaults:
      _controller: CjwPublishToolsBundle:StaticFile:output
      file: @CjwPublishToolsBundle/Resources/static/test.xml
      ttl: 0

cjwpublishtools_static_example_favicon:
    path: /favicon.ico
    defaults:
      _controller: CjwPublishToolsBundle:StaticFile:output
      file: @CjwPublishToolsBundle/Resources/static/favicon.ico
      ttl: 84600


*/


namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

# use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;


/**
 * Class StaticFileController
 * @package Cjw\PublishToolsBundle\Controller
 */
class StaticFileController extends Controller
{

    /**
     * @param string $file
     * @param int $locationId
     * @param int $ttl
     * @return Response
     */
    public function outputAction( $file = '@CjwPublishToolsBundle/Resources/static/index.html', $ttl = 86400, $locationId = 0 )
    {
        try
        {
            $filepath = $this->container->get('kernel')->locateResource( $file );
            //$path = $this->container->get('kernel.root_dir')->getParameter('kernel.root_dir').'Resources/static/index.html';

            // https://symfony.com/doc/current/components/http_foundation.html @see BinaryFileResponse

            // https://ourcodeworld.com/articles/read/329/how-to-send-a-file-as-response-from-a-controller-in-symfony-3


            $response = new Response;
            //$response = new BinaryFileResponse($path);

            if ( $ttl == 0 )
            {
                $response->setPrivate();
                //$response->setMaxAge( $ttl );

            }
            elseif ( $ttl > 0 )
            {
                $response->setPublic();
                //$response->setMaxAge( $ttl );
                $response->setSharedMaxAge( $ttl );
            }
            if ( $locationId > 0 )
            {
                $response->headers->set( 'X-Location-Id', (int) $locationId );
            }

            // To generate a file download, you need the mimetype of the file
            $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

            // Set the mimetype with the guesser or manually
            if ( $mimeTypeGuesser->isSupported() )
            {
                // Guess the mimetype of the file according to the extension of the file
                $response->headers->set('Content-Type', $mimeTypeGuesser->guess( $filepath ) );
            }
            else
            {
                // Set the mimetype of the file manually, in this case for a text file is text/plain
                $response->headers->set('Content-Type', 'text/plain');
            }


            //$response->headers->set( 'Content-Type', 'text/xml' )

            $response->setContent( file_get_contents( $filepath ) );
            return $response;
        }
        catch (\InvalidArgumentException $e)
        {
            throw $this->createNotFoundException(sprintf("File '%s' not exists!", $file ));
        }
    }

}