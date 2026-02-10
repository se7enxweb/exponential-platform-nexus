<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;


use eZFunctionHandler;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
//    public function indexAction( $template = 'CjwPublishToolsBundle:full:search.html.twig', Request $request )
    public function indexAction( $template = 'CjwPublishToolsBundle:full:search.html.twig', Request $request )
    {
        $searchText = $request->query->get( 'SearchText', '' );
        $subtree    = $request->query->get(
            'Subtree',
            $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' )
        );
        $section = $request->query->get( 'Section', null );
        $classID = $request->query->get( 'ClassID', null );
        $offset = $request->query->getInt( 'Offset', 0 );
        $limit  = $request->query->getInt( 'Limit', 7 );

        // ToDo: settings
        if ( $searchText !== false )
        {
//            $limit = $this->getConfigResolver()->getParameter( 'search.limit', 'todo' );


            $viewParameters = $request->attributes->get( 'viewParameters' );
            if ( isset( $viewParameters['offset'] ) )
            {
                $offset = $viewParameters['offset'];
            }

            if( isset( $viewParameters['limit'] ) )
            {
                $limit = $viewParameters['limit'];
            }

            //*
            $fetchArray = array();
            $fetchArray['text'] = $searchText;
            if ( isset( $subtree ) )
            {
                if ( !is_array( $subtree ) )
                    $subtree = explode( ',', $subtree );
                $fetchArray['subtree_array'] = $subtree;
            }
            if ( isset( $offset ) )
            {
                $fetchArray['offset'] = $offset;
            }
            if( isset( $limit ) )
            {
                $fetchArray['limit'] = (int) $limit;
            }
            if( isset( $section ) )
            {
                if ( !is_array( $section ) )
                    $section = explode( ',', $section );
                $fetchArray['section_id'] = $section;
            }
            if( isset( $classID ) )
            {
                if ( !is_array( $classID ) )
                    $classID = explode( ',', $classID );
                $fetchArray['class_id'] = $classID;
            }
            $fetchArray['sort_by'] = array( 'published', false );
            //*/


            /*
            $fetchArray = array();
            $fetchArray['SearchText'] = $searchText;
            if ( isset( $subtree ) )
            {
                if ( !is_array( $subtree ) )
                    $subtree = explode( ',', $subtree );
                $fetchArray['SearchSubtreeArray'] = $subtree;
            }
            if ( isset( $offset ) )
            {
                $fetchArray['SearchOffset'] = $offset;
            }
            if( isset( $limit ) )
            {
                $fetchArray['SearchLimit'] = $limit;
            }
            if( isset( $section ) )
            {
                if ( !is_array( $section ) )
                    $section = explode( ',', $section );
                $fetchArray['SearchSectionID'] = $section;
            }
            if( isset( $classID ) )
            {
                if ( !is_array( $classID ) )
                    $classID = explode( ',', $classID );
                $fetchArray['SearchContentClassID'] = $classID;
            }
            $fetchArray['SortArray'] = array( 'published', false );
            //*/

            // ToDo: Handle the Exception here, if an error occurs!
            /** @noinspection PhpUnhandledExceptionInspection */
            $resultList = $this->getLegacyKernel()->runCallback(
                function () use ( $fetchArray )
                {
                    /*
                    return \eZSearch::search( $fetchArray, $fetchArray );
                    //*/

                    //*
                    return eZFunctionHandler::execute(
                        'content',
                        'search',
                        $fetchArray
                    );
                    //*/
                }
            );

            $searchResult = array();
            $searchResult['searchHits'] = $resultList['SearchResult'];
            $searchResult['totalCount'] = $resultList['SearchCount'];
        }
        else
        {
            $searchResult = array();
            $searchResult['searchHits'] = array();
            $searchResult['totalCount'] = 0;
        }

        // ToDo: Add input parameters/arguments as template variables!
        return $this->render(
            $template,
            array(
                'results' => $searchResult,
                'searchText' => $searchText,
                'offset' => $offset,
                'limit' => $limit
            )
        );
    }

    /**
     * @return \eZ\Publish\Core\MVC\Legacy\Kernel
     */
    public function getLegacyKernel()
    {
        $legacyKernelClosure = $this->get('ezpublish_legacy.kernel');
        return $legacyKernelClosure();
    }
}
