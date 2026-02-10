<?php
/**
 * File containing the FormHandlerService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 */

namespace Cjw\PublishToolsBundle\Services;

use eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\Parser\Content;
use Symfony\Component\Templating\EngineInterface;

class FormHandlerService
{
    const separator = '___';
    protected $container;
    protected $em;
    protected $mailer;
    protected $templating;
    protected $formBuilderService;
    protected $twig;

    /**
     * init the needed services
     */
    public function __construct( $container, $em, \Swift_Mailer $mailer, EngineInterface $templating, $FormBuilderService )
    {
        $this->container = $container;
        $this->em = $em;
        $this->mailer = $mailer;
        $this->templating = $templating;
        $this->formBuilderService = $FormBuilderService;
        $this->twig = $container->get('twig');
    }

    /**
     * Adding collected form content to the old ez info collector
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return bool false
     */
    public function addToInfoCollectorHandler( $formDataObj, $handlerConfigArr, $handlerParameters, $form )
    {
        $content = $handlerParameters['content'];
        $contentType = $handlerParameters['contentType'];

        $formBuilderService = $this->container->get( 'cjw_publishtools.formbuilder.functions' );

        $timestamp = time();

        // get table from db (services.yml)
        $ezinfocollection = $this->container->get( 'db_table_ezinfocollection' );

        // add new collection
        $ezinfocollectionRow = new $ezinfocollection();

        $ezinfocollectionRow->set( 'contentobject_id', $handlerParameters['contentObjectId'] );
        $ezinfocollectionRow->set( 'user_identifier', '' );
        $ezinfocollectionRow->set( 'creator_id', $handlerParameters['currentUserId'] );
        $ezinfocollectionRow->set( 'created', $timestamp );
        $ezinfocollectionRow->set( 'modified', $timestamp );

        $this->em->persist( $ezinfocollectionRow );
        $this->em->flush();

        $informationcollectionId = $ezinfocollectionRow->getId();

        // get table from db (services.yml)
        $ezinfocollectionAttribute = $this->container->get( 'db_table_ezinfocollection_attribute' );

        // add collection attribute
        foreach( $formDataObj as $key => $attribute )
        {
            $keyArr = explode( FormHandlerService::separator, $key );
            $fieldType = $keyArr['0'];
            $fieldIdentifier = $keyArr['1'];

            $data_float = 0;
            $data_int = 0;
            $data_text = '';

            switch ( $fieldType )
            {
                case 'ezxml':
                    $data_text =  $formBuilderService->newEzXmltextSchema( $attribute );
                    break;
                case 'ezdate':
                    if(is_object($attribute))
                    {
                        $data_int = $attribute->getTimestamp();
                    }
                    break;
                case 'ezboolean':
                        $data_int = (int) $attribute;
                    break;
                default:
                    $data_text = (string) $attribute;
            }

            $ezinfocollectionAttributeRow = new $ezinfocollectionAttribute();
            $ezinfocollectionAttributeRow->set( 'contentobject_id', $handlerParameters['contentObjectId'] );
            $ezinfocollectionAttributeRow->set( 'informationcollection_id', $informationcollectionId );
            $ezinfocollectionAttributeRow->set( 'contentclass_attribute_id', $contentType[$fieldIdentifier]->id );
            $ezinfocollectionAttributeRow->set( 'contentobject_attribute_id', $content->getField($fieldIdentifier)->id );
            $ezinfocollectionAttributeRow->set( 'data_float', $data_float );
            $ezinfocollectionAttributeRow->set( 'data_int', $data_int );
            $ezinfocollectionAttributeRow->set( 'data_text', $data_text );

            $this->em->persist( $ezinfocollectionAttributeRow );
//            $this->em->flush();  => do not flush inside a loop!
        }

        $this->em->flush();

        return false;
    }

    /**
     * Builds and sending an email, renders the email body with an twig template
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return bool false
     */
    public function sendEmailHandler( $formDataObj, $handlerConfigArr, $handlerParameters, $form )
    {
        $content = false;
        if ( isset( $handlerParameters['content'] ) )
        {
            $content = $handlerParameters['content'];
        }

        $location = false;
        if ( isset( $handlerParameters['location'] ) )
        {
            $location = $handlerParameters['location'];
        }

        $app = false;
        if ( isset( $handlerParameters['app'] ) )
        {
            $app = $handlerParameters['app'];
        }

        $formDataArr = $this->getFormDataArray( $formDataObj );

        $template = false;
        if ( isset( $handlerConfigArr['template'] ) )       // ToDo: more checks
        {
            $template = $this->formBuilderService->getTemplateOverride( $handlerConfigArr['template'] );
        }

        $subject = false;
        if ( isset( $handlerConfigArr['email_subject'] ) )        // ToDo: more checks
        {
            if ( substr( $handlerConfigArr['email_subject'], 0, 1 ) === '@' )
            {
                $subject_mapping = substr( $handlerConfigArr['email_subject'], 1 );
                if ( isset( $formDataArr[$subject_mapping]['value'] ) )
                {
                    $subject = $formDataArr[$subject_mapping]['value'];
                }
                // If EMail Subject is set as fieldvalue and not as informationcollector
                elseif ( $content->getFieldValue( $subject_mapping ) != null )
                {
                    $subject = (string) $content->getFieldValue( $subject_mapping );
                }
            }
            else
            {
                $subject = $handlerConfigArr['email_subject'];
            }
        }

        $from = false;
        if ( isset( $handlerConfigArr['email_sender'] ) )
        {
            if ( substr( $handlerConfigArr['email_sender'], 0, 1 ) === '@' )
            {
                $email_sender_mapping = substr( $handlerConfigArr['email_sender'], 1 );
                if ( isset( $formDataArr[$email_sender_mapping]['value'] ) )
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $formDataArr[$email_sender_mapping]['value'], FILTER_VALIDATE_EMAIL ) )
                    {
                        $from = $formDataArr[$email_sender_mapping]['value'];
                    }
                }
                // If EMail Sender is set as fieldvalue and not as informationcollector
                elseif ( $content->getFieldValue( $email_sender_mapping ) != null )
                {
                    $fromTmp = trim( (string)$content->getFieldValue( $email_sender_mapping ) );
                    if ( filter_var( $fromTmp ,FILTER_VALIDATE_EMAIL ) )
                    {
                        $from = $fromTmp;
                    }
                }
            }
            else
            {
                // Check email addresses validity by using PHP's internal filter_var function
                if( filter_var( $handlerConfigArr['email_sender'], FILTER_VALIDATE_EMAIL ) )
                {
                    $from = $handlerConfigArr['email_sender'];
                }
            }
        }

        $to = array();
        $receiver_collection = array();
        if ( isset( $handlerConfigArr['email_receiver'] ) )
        {
            $to = $handlerConfigArr['email_receiver'];

            if ( is_string($handlerConfigArr['email_receiver'] ) )
            {
                $to = array( $handlerConfigArr['email_receiver'] );
            }

            for( $a=0; $a < count( $to ); $a++ )
            {
                if ( substr( $to[$a], 0, 1 ) === '@' )
                {
                    $email_receiver_mapping = substr( $to[$a], 1 );

                    if( isset( $formDataArr[$email_receiver_mapping]['value'] ) )
                    {
                        // Check email addresses validity by using PHP's internal filter_var function
                        if ( filter_var( $formDataArr[$email_receiver_mapping]['value'], FILTER_VALIDATE_EMAIL ) )
                        {
                            array_push( $receiver_collection, $formDataArr[$email_receiver_mapping]['value']);
                        }
                    }
                    // If EMail Reciever is set as fieldvalue and not as informationcollector
                    elseif ( $content->getFieldValue( $email_receiver_mapping ) != null )
                    {
                        // __toString triggern
                        $emailString = (string) $content->getFieldValue( $email_receiver_mapping );

                        // extract multiple mails separated bei ,
                        $emailArray = explode( ',', $emailString );
                        foreach( $emailArray as $email )
                        {
                            if ( filter_var( trim( $email ) ,FILTER_VALIDATE_EMAIL ) )
                            {
                                array_push( $receiver_collection, trim( $email ) );
                            }
                        }

                    }
                }
                else
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $to[$a], FILTER_VALIDATE_EMAIL ) )
                    {
                        array_push( $receiver_collection, $to[$a] );
                    }
                }
            }
        }

        $to = $receiver_collection;

        $logging = false;
        if ( isset( $handlerConfigArr['logging'] ) && $handlerConfigArr['logging'] === true )
        {
            $logging = true;
        }

        $debug = false;
        if ( isset( $handlerConfigArr['debug'] ) && $handlerConfigArr['debug'] === true )
        {
            $debug = true;
        }

//      ToDo: logic for check and manipulate fields must transfer in separate private function
        $bcc = array();
        if( isset( $handlerConfigArr['email_bcc'] ) && !is_array( $handlerConfigArr['email_bcc'] ) ) {
            if( substr( $handlerConfigArr['email_bcc'], 0, 1 ) === '@' ) {
                $email_receiver_mapping = substr( $handlerConfigArr['email_bcc'], 1 );
                if( isset( $formDataArr[$email_receiver_mapping]['value'] ) ) {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $formDataArr[$email_receiver_mapping]['value'], FILTER_VALIDATE_EMAIL ) ) {
                        array_push( $bcc, trim( $formDataArr[$email_receiver_mapping]['value'] ) );
                    } elseif ( $content->getFieldValue( $email_receiver_mapping ) != null ) {
                    // If EMail Bcc is set as fieldvalue and not as informationcollector
                        // __toString triggern
                        $emailString = (string) $content->getFieldValue( $email_receiver_mapping );

                        // extract multiple mails separated bei ,
                        $emailArray = explode( ',', $emailString );
                        foreach( $emailArray as $email ) {
                            if ( filter_var( trim( $email ) ,FILTER_VALIDATE_EMAIL ) ) {
                                array_push( $bcc, trim( $email ) );
                            }
                        }
                    }
                }
            } else {
                // Check email addresses validity by using PHP's internal filter_var function
                if ( filter_var( $handlerConfigArr['email_bcc'], FILTER_VALIDATE_EMAIL ) ) {
                    array_push( $bcc, trim( $handlerConfigArr['email_bcc'] ) );
                }
            }
        } elseif( isset($handlerConfigArr['email_bcc']) && is_array( $handlerConfigArr['email_bcc'] ) ) {
            foreach( $handlerConfigArr['email_bcc'] as $mail_adress_item ) {
                if( substr( $mail_adress_item, 0, 1 ) === '@' ) {
                    $email_receiver_mapping = substr( $mail_adress_item, 1 );
                    if( isset( $formDataArr[$email_receiver_mapping]['value'] ) ) {
                        // Check email addresses validity by using PHP's internal filter_var function
                        if( filter_var( $formDataArr[$email_receiver_mapping]['value'], FILTER_VALIDATE_EMAIL ) ) {
                            $bcc[] = $formDataArr[$email_receiver_mapping]['value'];
                        }
                    } elseif( $content->getFieldValue( $email_receiver_mapping ) != null ) {
                    // If EMail Bcc is set as fieldvalue and not as informationcollector
                        // __toString triggern
                        $emailString = (string) $content->getFieldValue( $email_receiver_mapping );

                        // extract multiple mails separated bei ,
                        $emailArray = explode( ',', $emailString );
                        foreach( $emailArray as $email ) {
                            if( filter_var( trim( $email ) ,FILTER_VALIDATE_EMAIL ) ) {
                                array_push( $bcc, trim( $email ) );
                            }
                        }
                    }
                } else {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if( filter_var( $mail_adress_item, FILTER_VALIDATE_EMAIL ) ) {
                        array_push( $bcc, trim( $mail_adress_item ) );
                    }
                }
            }
        } else {
            // Check email addresses validity by using PHP's internal filter_var function
            if( isset( $handlerConfigArr['email_bcc'] ) && filter_var( $handlerConfigArr['email_bcc'], FILTER_VALIDATE_EMAIL ) ) {
                $bcc = $handlerConfigArr['email_bcc'];
            }
        }

//      ToDo: logic for check and manipulate fields must transfer in separate private function
        $cc = array();
        if( isset($handlerConfigArr['email_cc']) && !is_array( $handlerConfigArr['email_cc'] ) )
        {
            if( substr( $handlerConfigArr['email_cc'], 0, 1 ) === '@' )
            {
                $email_receiver_mapping = substr( $handlerConfigArr['email_cc'], 1 );
                if( isset( $formDataArr[$email_receiver_mapping]['value'] ) )
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $formDataArr[$email_receiver_mapping]['value'], FILTER_VALIDATE_EMAIL ) )
                    {
                        array_push( $cc, trim( $formDataArr[$email_receiver_mapping]['value'] ) );
                    }
                }
                // If EMail CC is set as fieldvalue and not as informationcollector
                elseif ( $content->getFieldValue( $email_receiver_mapping ) != null )
                {
                    // __toString triggern
                    $emailString = (string) $content->getFieldValue( $email_receiver_mapping );

                    // extract multiple mails separated bei ,
                    $emailArray = explode( ',', $emailString );
                    foreach( $emailArray as $email )
                    {
                        // Check email addresses validity by using PHP's internal filter_var function
                        if ( filter_var( trim( $email ), FILTER_VALIDATE_EMAIL ) )
                        {
                            array_push( $cc, trim( $email ) );
                        }

                    }

                }
            }
            else
            {
                // Check email addresses validity by using PHP's internal filter_var function
                if ( filter_var( $handlerConfigArr['email_cc'], FILTER_VALIDATE_EMAIL ) )
                {
                    $cc = $handlerConfigArr['email_cc'];
                }
            }
        }
        elseif( isset($handlerConfigArr['email_cc']) && is_array( $handlerConfigArr['email_cc'] ) )
        {
            foreach( $handlerConfigArr['email_cc'] as $mail_adress_item ) {
                if( substr( $mail_adress_item, 0, 1 ) === '@' )
                {
                    $email_receiver_mapping = substr( $mail_adress_item, 1 );
                    if( isset( $formDataArr[$email_receiver_mapping]['value'] ) )
                    {
                        // Check email addresses validity by using PHP's internal filter_var function
                        if ( filter_var( $formDataArr[$email_receiver_mapping]['value'], FILTER_VALIDATE_EMAIL ) )
                        {
                            $cc[] = $formDataArr[$email_receiver_mapping]['value'];
                        }
                    }
                    // If EMail CC is set as fieldvalue and not as informationcollector
                    elseif ( $content->getFieldValue( $email_receiver_mapping ) != null )
                    {
                        // __toString triggern
                        $emailString = (string) $content->getFieldValue( $email_receiver_mapping );

                        // extract multiple mails separated bei ,
                        $emailArray = explode( ',', $emailString );
                        foreach( $emailArray as $email )
                        {
                            if ( filter_var( trim( $email ) ,FILTER_VALIDATE_EMAIL ) )
                            {
                                array_push( $cc, trim( $email ) );
                            }
                        }

                    }
                }
                else
                {
                    // Check email addresses validity by using PHP's internal filter_var function
                    if ( filter_var( $mail_adress_item, FILTER_VALIDATE_EMAIL ) )
                    {
                        $cc[] = $mail_adress_item;
                    }
                }
            }
        }
        else
        {
            if ( isset($handlerConfigArr['email_cc']) )
            {
                if ( filter_var( trim( $handlerConfigArr['email_cc'] ) ,FILTER_VALIDATE_EMAIL ) )
                {
                    $cc = trim( $handlerConfigArr['email_cc'] );
                }

            }
        }

        if( $template !== false && $subject !== false && $from !== false && $to !== false ) {
// ToDo: render template inline if $template false
            $templateContent = $this->twig->loadTemplate($template);

            $templateParameters = array( 'form' => $form->createView(),
                                         'form_data_array' => $formDataArr,
                                         'form_data_object' => $formDataObj,
                                         'content' => $content,
                                         'location' => $location,
                                         'app' => $app );

           $bodyTextHtml = $templateContent->renderBlock('body_text_html', $templateParameters );


            $bodyTextPlain = $templateContent->renderBlock('body_text_plain', $templateParameters );

/*
            $message = \Swift_Message::newInstance()
//                ->setEncoder(\Swift_Encoding::get7BitEncoding())
//                ->setCharset('UTF-8')
                ->setEncoder( \Swift_Encoding::get8BitEncoding() )
                ->setSubject( $subject )
                ->setFrom( $from )
                ->setTo( $to )
                ->setBcc( $bcc )
                ->setCc( $cc )
                ->setBody( $bodyTextHtml, 'text/html' )
                ->addPart( $bodyTextPlain, 'text/plain' );
*/
            $message = ( new \Swift_Message( $subject ) )
                ->setFrom( $from )
                ->setTo( $to )
                ->setBcc( $bcc )
                ->setCc( $cc )
                ->setBody( $bodyTextHtml, 'text/html' )
                ->addPart( $bodyTextPlain, 'text/plain' );
            ;

            if( $debug === false ) {
               $this->mailer->send( $message );
            }

            if( $logging === true ) {
                $msgId = substr( $message->getHeaders()->get( 'Message-ID' )->getFieldBody(), 1, -1 );

//                $dump = $message->toString(); // <- this is the "real" output sent by the mailer
                $dump = $message->getHeaders()->toString()."\n\n".$bodyTextHtml."\n\n".$bodyTextPlain; // <- this is the "clean" uncoded output fetched directly from the template
//                $dump = str_replace( 'search', '', $message->toString() );

                $log_dir = $this->container->getParameter( 'kernel.logs_dir' ) . '/formbuilder/';

                if( is_dir( $log_dir ) === false ) {
                    mkdir( $log_dir );
                }

                file_put_contents( $log_dir.time() . '_' . $msgId, $dump );
            }
        } else {
            if( $debug === true ) {
                $error =
                    'Error: All parameters ($template, $subject, $from, $to) must be provided <br> <br>'.
                    "template : $template <br>subject: $subject<br>from: $from<br>to: ". print_r( $to, true ) ."<br> <br>".
                    'This error was thrown on line: <font color="#5f9ea0">'.__LINE__.'</font><br>'.
                    'Of file: <font color="#5f9ea0">'.__FILE__. '</font><br>'.
                    'Inside of function: <font color="#5f9ea0">'.__FUNCTION__.'</font> <br>'.
                    '<br> <font color="red">Warning: </font> if this error is shown in production, disable debug!';

                die( $error );
            } else {
                die( "search for: error code #244" );
            }
        }

        return false;
    }

    /**
     * Handle a success action
     *
     * @param mixed $formDataObj
     * @param array $handlerConfigArr
     * @param mixed $handlerParameters
     *
     * @return result
     */
    public function successHandler( $formDataObj, $handlerConfigArr, $handlerParameters, $form )
    {
        $content = false;
        if ( isset( $handlerParameters['content'] ) )
        {
            $content = $handlerParameters['content'];
        }

        $location = false;
        if ( isset( $handlerParameters['location'] ) )
        {
            $location = $handlerParameters['location'];
        }

        $template = $this->formBuilderService->getTemplateOverride( $handlerConfigArr['template'] );

        $formDataArr = $this->getFormDataArray( $formDataObj );

        // ToDo: template checks, if false render inline
        return $this->templating->render(
            $template,
            array( 'form' => $form->createView(),
                   'form_data_array' => $formDataArr,
                   'form_data_object' => $formDataObj,
                   'content' => $content,
                   'location' => $location )
        );
    }

    /**
     * ToDo
     */
    public function contentAddHandler()
    {
        return false;
    }

    /**
     * ToDo
     */
    public function contentEditHandler()
    {
        return false;
    }

    protected function getFormDataArray( $formDataObj )
    {
        $formDataArr = array();
        foreach ( $formDataObj as $formIdentifier => $formValue )
        {
            // ezstring:first_name  => ezstring__first_name
            $keyArr = explode( FormHandlerService::separator, $formIdentifier );
            // ezstring
            $contentType = $keyArr['0'];
            // first_name
            $fieldIdentifier = $keyArr['1'];
            // ezuser email
            if ( isset( $keyArr['2'] ) )
            {
                $fieldIdentifier = $fieldIdentifier.FormHandlerService::separator.$keyArr['2'];
            }

            $formDataArr[$fieldIdentifier] = array( 'value' => $formValue,
                'content_type'  => $contentType,
                'field_identifier' => $fieldIdentifier,
                'form_identifier' => $formIdentifier
            );
        }

        return $formDataArr;
    }
}
