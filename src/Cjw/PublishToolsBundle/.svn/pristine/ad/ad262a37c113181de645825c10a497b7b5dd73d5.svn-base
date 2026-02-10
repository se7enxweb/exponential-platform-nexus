<?php

declare(strict_types=1);

namespace Cjw\PublishToolsBundle\Controller;

use Netgen\Bundle\EzPlatformSiteApiBundle\Controller\Controller;
use Netgen\Bundle\EzPlatformSiteApiBundle\View\ContentView;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect file full view to file download uri (ngsite/downlowd)
 */
class DownloadFileController extends Controller
{
    public function __construct() {}

    /**
     * Get file uri and redirect to it.
     */
    public function __invoke(ContentView $view)
    {
        $content   = $view->getSiteContent();
        $fileField = $content->getField( 'file' );

        if ( $fileField->isEmpty() === FALSE )
        {
            $binaryFieldValue = $fileField->value;

            return $this->redirect( $binaryFieldValue->uri . '/1' );
        }

        return $view;
    }
}
