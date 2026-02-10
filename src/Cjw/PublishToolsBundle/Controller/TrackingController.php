<?php
/**
 * File containing the TrackingController class
 *
 * @copyright Copyright (C) 2007-2014 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class TrackingController
 * @package Cjw\PublishToolsBundle\Controller
 */
class TrackingController extends Controller
{
    /**
     * Main action for viewing a template without using actual content from the
     * database – you're able to fetch it from the template by yourself though.
     *
     * @param string $template
     *
     * @return Response
     */
    public function viewPageAction( $template, Request $request = null )
    {
        if ( !$request )
        {
            $request = $this->getRequest();
        }

        return $this->render(
            $template,
            array( 'hostname' => $request->getHost() )
        );
    }
    /**
     * Main action for viewing a template without using actual content from the
     * database – you're able to fetch it from the template by yourself though.
     *
     * @param string $template
     *
     * @return Response
     */
    public function statisticAction( Request $request = null )
    {
//        if (!$request->isXmlHttpRequest())
//        {
//            return new JsonResponse(array(
//                'status' => 'Error',
//                'message' => 'Error'),
//                400);
//        }
//
//        // The URL to your Matomo server.
//        $matomoUrl = 'stats.jac-systeme.de/piwik.php';
//
//        // The maximum timeout in seconds to wait for the Matomo server to respond.
//        $timeout = 5;
//
//        // The GET parameters forwarded to Matomo.
////        $parameters = $_GET;
//        $parameters = $request;
//
//        // Forward the request to Matomo and fetch the response.
//        $options = array('http' => array('ignore_errors' => true, 'timeout' => $timeout));
//        $context = stream_context_create($options);
//
//        // Prepare URL
//        $matomoUrl .= '?' . http_build_query($parameters);
//
//        // Fetch and return the response from Matomo
//        $response = @file_get_contents($matomoUrl, false, $context);
//
//        // If something went wrong, display a default error message
//        if ($response === false) {
//            header("HTTP/1.0 500 Internal Server Error");
//            die("An error occurred while loading the page.");
//        } else {
//            // Forward the HTTP response code.
//            header(sprintf('HTTP/1.1 %d %s', $http_response_header[0]), true);
//            // Print response from Matomo.
//            echo $response;
//        }

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(array(
                'status' => 'Error',
                'message' => 'Error'),
                400);
        }

        $trackingId = $this->getParameter('cjwsite.default.piwik.tracking_id');
        $matomoUrl  = 'https://'.$this->getParameter('cjwsite.default.piwik.tracking_url').'/piwik.php?idsite='.$trackingId;
        // The URL to your Matomo server.
//        $matomoUrl = 'https://stats.jac-systeme.de/piwik.php?idsite=6';

        // The maximum timeout in seconds to wait for the Matomo server to respond.
        $timeout = 5;

        // The GET parameters forwarded to Matomo.
        $parameters = $request->query->all();

        // Prepare URL
//        $matomoUrl .= '?' . http_build_query($parameters);

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $matomoUrl);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // set timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // disable SSL certificate verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // $output contains the output string
        $response = curl_exec($ch);

        // get HTTP response code
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close curl resource to free up system resources


        if($response === false) {
            return new JsonResponse(array(
                'status' => 'Error',
                'message' => curl_error($ch)),
                500);
        } else {
            // Forward the HTTP response code.
            http_response_code($httpcode);
            // Print response from Matomo.
            //echo $response;

        }
        curl_close($ch);
        return new JsonResponse(array(
            'status' => 'Success',
            'message' => 'OK'),
            200);
    }
    public function trackAction()
    {
        // Angenommen, Sie haben eine Benutzermethode, um zu prüfen, ob das Tracking aktiviert ist
//        $benutzer = $this->getUser();$benutzer->hasTrackingEnabled() ||
        $timeout = 5;

            // create curl resource
            $ch = curl_init();

            // set url
            curl_setopt($ch, CURLOPT_URL, 'https://stats.jac-systeme.de/piwik.js');

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // set timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            // disable SSL certificate verification
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // $output contains the output string
            $response = curl_exec($ch);

            // get HTTP response code
//            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//            $client = new Client();
//            $response = $client->request('GET', 'https://stats.jac-systeme.de/piwik.js');

            // Hier geben wir den Inhalt von piwik.js direkt zurück
            return new Response($response, Response::HTTP_OK, [
                'Content-Type' => 'application/javascript',
            ]);


        // Wenn das Tracking nicht aktiviert ist oder etwas fehlgeschlagen ist, geben wir eine leere Antwort zurück
        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
