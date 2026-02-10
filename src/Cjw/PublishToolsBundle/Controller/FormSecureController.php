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

use Cjw\Cjw\PublishToolsBundle\Form\Captcha\Captcha;
use eZ\Publish\Core\MVC\Symfony\View\LocationValueView;
use Netgen\Bundle\InformationCollectionBundle\Controller\InformationCollectionController;
use eZ\Publish\Core\MVC\Symfony\View\CachableView;
use eZ\Publish\Core\MVC\Symfony\View\ContentValueView;
use Netgen\Bundle\InformationCollectionBundle\Event\InformationCollected;
use Netgen\Bundle\InformationCollectionBundle\Events;
use Netgen\Bundle\InformationCollectionBundle\InformationCollectionTrait;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class FormController
 * @package Cjw\PublishToolsBundle\Controller
 */
class FormSecureController extends InformationCollectionController
{
    use InformationCollectionTrait;
    use ContainerAwareTrait;

    /**
     * Displays and handles information collection.
     *
     * @param ContentValueView $view
     * @param Request $request
     *
     * @return ContentValueView
     */
    public function displayAndHandle(ContentValueView $view, Request $request)
    {
//        if (session_status() === PHP_SESSION_NONE) {
//            session_start();

//        }


        /** @var \Netgen\Bundle\InformationCollectionBundle\Form\Builder\FormBuilder $formBuilder */
        $formBuilder = $this->container
            ->get('netgen_information_collection.form.builder');

        $form = $formBuilder->createFormForLocation($view->getLocation())
            ->getForm();

        $form->handleRequest($request);

//        $response = new Response(self::displayArrayRecursively($form->getData()), Response::HTTP_OK);
//        $response = new Response(var_dump(($form->get('bot_protection'))), Response::HTTP_OK);
//        $response = new Response($this->displayArrayRecursively($form->getData()->payload->getCollectedFields()), Response::HTTP_OK);
//        return $response;
//            var_dump ($form->getData()->payload->getCollectedFields()['bot_protection']->bool);
        if ( isset( $form->getData()->payload->getCollectedFields()['bot_protection'] ) && $form->getData()->payload->getCollectedFields()['bot_protection']->bool  ){

            $response = new Response('Thank you for Trust.', Response::HTTP_OK);
            $view->setResponse($response);
            return $response;
        }
//        if ( isset( $_SESSION['captcha_solved'] ) && !$_SESSION['captcha_solved']) return $view;


        $parameters = $this->collectInformation($view, $request);


        $view->addParameters($parameters);

        if ($view instanceof CachableView) {
            $view->setCacheEnabled(false);
        }

//        return new Response(self::displayArrayRecursively($view), Response::HTTP_OK);
//       return self::displayArrayRecursively($view);
        return $view;

    }
    protected function collectInformation(ContentValueView $view, Request $request)
    {
        $isValid = false;

        if (!$view instanceof LocationValueView) {
            throw new \BadMethodCallException('eZ view needs to implement LocationValueView interface');
        }

        /** @var \Netgen\Bundle\InformationCollectionBundle\Form\Builder\FormBuilder $formBuilder */
        $formBuilder = $this->container
            ->get('netgen_information_collection.form.builder');

        $form = $formBuilder->createFormForLocation($view->getLocation())
            ->getForm();

        $form->handleRequest($request);
//        $validCaptcha = false;
        $captcha = new Captcha();
        $validCaptcha = $captcha->isValid();

        $formSubmitted = $form->isSubmitted();

        if ($formSubmitted && $form->isValid() && $validCaptcha) {
            $isValid = true;
            $event = new InformationCollected($form->getData());

            /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
            $dispatcher = $this->container
                ->get('event_dispatcher');

            $dispatcher->dispatch(Events::INFORMATION_COLLECTED, $event);
        }

        if (true === $formSubmitted && false === $validCaptcha) {
//            $form->addError(new FormError($this->container->get('translator')->trans('form.errors.captcha_failed', array(), 'netgen_information_collection_form_messages')));
            $form->addError(new FormError('Bitte den Spamschutz erneut eingeben','message_template', [
                "name" => "captcha_error",
            ]));
        }
//        session_abort();
        return array(
            'is_valid' => $isValid,
            'form' => $form->createView(),
            'collected_fields' => $form->getData()->payload->getCollectedFields(),
        );
    }

    public function secureAction( Request $request = null )
    {

        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(array(
                'status' => 'Error',
                'message' => 'Error'),
                400);
        }
        if ( isset ( $request->request )  ) {
//            if ( session_status() === PHP_SESSION_NONE ) {
//                session_start();
//            }
            $sessionCaptcha = $_SESSION['captcha_string'];
            $captcha = $request->request->get('text');
            if ( Captcha::checkCaptcha($captcha, $sessionCaptcha) ) {
                // Captcha ist gültig
                $_SESSION['captcha_solved'] = true;
                return new JsonResponse(array(
                    'status' => 'solved',
                    'message' => 'yeah'),
                    200);

//            if ( $captcha == $_SESSION['captcha_string'] ) {
//                $_SESSION['captcha_solved'] = true;
//                return new JsonResponse(array(
//                    'status' => 'yeah',
//                    'message' => 'yeah'),
//                    200);
            }
        }
        return new JsonResponse(array(
            'status' => 'unsolved',
//            'message' => $_SESSION['captcha_string']),
            'message' => $captcha,
            200)
        );
    }
    /**
     * Check whether a request uri seems to be a version view request or not.
     *
     * @param
     *
     * @return string
     */
    public function buildCaptchaAction()
    {
//        if (session_status() === PHP_SESSION_NONE) {
//            session_start();
//
//        }
        $captcha = new Captcha();
        $img = $captcha->buildAndRegisterCaptcha();

        header('Expires: Mon, 26 Jul 1990 05:00:00 GMT');
        header("Last-Modified: ".date("D, d M Y H:i:s")." GMT");
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Content-type: image/png');

        return new JsonResponse(array( "image" => $img,));
        return new StreamedResponse(function () use ($img) {
            return $img;
        }, 200, ['Content-Type' => 'image/png']);

//            $response->headers->set('Content-Type','image/png');
//        return $response;
    }
//     function displayArrayRecursively( $array, $key = 'unset', $keysString = '', $margin = 30, $rgb = 0,$nbsp = ''  )
//    {
//        if (!isset($key))$key = 'unset';
//        $key = htmlspecialchars($key);
////        echo '<h4>  [ ' . var_dump($key) . ']</h4>';
//
//
//        if ( $rgb == 0 ) $rgb = self::getRgbColor();
//        $rgb1 = self::getRgbColor( $rgb,'b',-25,false,1 );
//        $rgb1 = self::getRgbColor( $rgb,'g',-15,false,1 );
//        $rgb1 = self::getRgbColor( $rgb,'r',-15,false,1 );
//
//         $html =
//        "<h4>".
////             "$key".' =>  [ ' .
////
////            var_dump($key) .
////             ']'.
//
//        "</h4>".
//        "<div style='".
//            "float: left;width: 90%;".
//            "background-color:rgb(".  $rgb['colorString']." );".
//            "margin-left: ".$margin."px;".
//            "margin-bottom: 5px;".
//            "'".
//        ">";
//
//            if ( is_object($array)) {
////            $array = json_decode( ( json_encode($array) ) , true);
//                $array = json_encode($array) ;
//            }
//            if ( is_array ( $array ) ) {
//                $html .=
//                "<h3>".
////
//                    //echo '+ '.$key ;
//
//                "</h3>";
//
//                $nbsp = $nbsp.'&nbsp;&nbsp;&nbsp;&nbsp;';
//                $rgb = $rgb1;
//
//                foreach ($array as $key => $value) {
//
//
//                    //displayArrayRecursively($value, $keysString . $key . '.',($margin+20));
//                    $html .= $this->displayArrayRecursively($value,$key,$keysString.'<br> '. $nbsp .' => '.$key,($margin),  $rgb, $nbsp   );
//                }
//
//
//
//
//            }
//            else {
//                $rgb2 = self::getRgbColor( $rgb1,'b',-25,false,1 );
//                $rgb2 = self::getRgbColor( $rgb1,'g',-15,false,1 );
//                $rgb2 = self::getRgbColor( $rgb1,'r',-15,false,1 );
////            $rgb = StyleAssistent::getRgbColor( $rgb,'r',-15,false,-1 );
//                $nbsp = $nbsp.'&nbsp;&nbsp;&nbsp;&nbsp;';
//                $html .=
//                "<h3>".
//                     '[ '.$key. ']'. ' = '.$array .
//                "</h3>".
//
//
//                "<div style='
//                    width: 100%;
//                    border: solid 1px black;
//                    background-color:rgb( " .$rgb1['colorString']." );
//                    margin-left:".$margin."px;
//                    margin-bottom: 5px;
//                    '
//                >".
//
//                    '<br>'.'<br>'.$keysString.'<br>'. $nbsp .' = '. $array .
//
//
//                "<br/>
//
//                </div>";
//
//
//
//            }
//        $html.=
//        '</div>
//        <div style="clear: both">
//        </div>
//        ';
//         return $html;
//    }
//    static function getRgbColor ( $rgb = false, $part = false, $changeValue = false, $random = false, $light = 1) {
//
//        if( $rgb == false ) {
//
//            if( $random == false ) {
//                $rgb['r']=124+( random_int(0,40) * $light );
//                $rgb['g']=124+( random_int(0,40) * $light );
//                $rgb['b']=124+( random_int(0,40) * $light );
//            }
//            else {
//
//                $rgb['r']=random_int(40,200)+( random_int(0,40) * $light );
//                $rgb['g']=random_int(40,200)+( random_int(0,40) * $light );
//                $rgb['b']=random_int(40,200)+( random_int(0,40) * $light );
//            }
//
//        }
//        else {
//            if( $part != false && $changeValue != false) {
//                if ( $light == 0 ) {
//                    $rgb[$part] += ( random_int(10,30) * $light );
//                }
//                else $rgb[$part] += ( random_int(10,30) * $light );
//                if ( $rgb[$part] <= (40 +  40 * $light) ) $rgb[$part] = random_int(40,200)+( random_int(0,40) * $light );
//                if ( $rgb[$part] >= (200 + 40 * $light )) $rgb[$part] = random_int(40,200)+( random_int(0,40) * $light );
//            }
//            else
//                if( $changeValue != false) {
//                    if ( $light == 0 ) {
//                        $rgb['r'] += ( random_int(10,30) * $light );
//                        $rgb['g'] += ( random_int(10,30) * $light );
//                        $rgb['b'] += ( random_int(10,30) * $light );
//                    }
//                    else {
//                        $rgb['r'] += ( random_int(10,30) * $light );
//                        $rgb['g'] += ( random_int(10,30) * $light );
//                        $rgb['b'] += ( random_int(10,30) * $light );
//                    }
//                    foreach ($rgb as $part){
//                        if ( $rgb[$part] <= (45 +  35 * $light) ) $rgb[$part] = random_int(40,200)+( random_int(0,40) * $light );
//                        if ( $rgb[$part] >= (200 + 40 * $light )) $rgb[$part] = random_int(40,200)+( random_int(0,40) * $light );
//                    }
//
//                }
//
//        }
//        $rgb['colorString'] = $rgb['r'].', '.$rgb['g'].', '.$rgb['b'];
//        return $rgb;
//    }
}
