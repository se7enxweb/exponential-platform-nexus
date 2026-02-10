<?php

namespace Cjw\PublishToolsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;

use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use eZFunctionHandler;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;


//use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\ContentTypeService;

use eZ\Publish\API\Repository\Values\Content\Query\FacetBuilder;

use Kaliop\EzFindSearchEngineBundle\API\Repository\Values\Content\Query\FacetBuilder as KaliopFacetBuilder;

/**
 * TEST Searchcontroller over the ezplatform api
 *
 * routing.yaml

        # ezplatform suche - test ggf. eigenen Controller schreiben der SiteAPI Objekte zurückliefert + eigenes template übergeben
        # /contentsearch?SearchText=meist&SearchButton=Suchen
        cjwpublishtools_contentsearch:
            path: /contentsearch
            defaults:
            _controller: CjwPublishToolsBundle:SearchPlatform:search
            template: 'CjwPublishToolsBundle:full:search_platform.html.twig'
            #        template: 'AppBundle:full:search.html.twig'
 *
 *
 * Class SearchPlatformController
 * @package Cjw\PublishToolsBundle\Controller
 */
class SearchPlatformController extends Controller
{

    public function searchAction( $template = 'CjwPublishToolsBundle:full:search_platform.html.twig', Request $request )
    {
        $repository = $this->getRepository();
        $configResolver = $this->getConfigResolver();
        $rootLocation = $this->getRootLocation();

        $publishToolsService = $this->get( 'cjw_publishtools.service.functions' );
//        $rootLocationId = $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' );




        $searchText = $request->query->get( 'SearchText', '' );
        $subtree    = $request->query->get(
            'Subtree',
            $this->getConfigResolver()->getParameter( 'content.tree_root.location_id' )
        );


        $section = $request->query->get( 'Section', null );
        $classID = $request->query->get( 'ClassID', null );
        $offset = $request->query->getInt( 'Offset', 0 );
        $limit  = $request->query->getInt( 'Limit', 7 );


        $searchResult = array();
        $searchResult['searchHits'] = array();
        $searchResult['totalCount'] = 0;

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




// legacy search
//            // ToDo: Handle the Exception here, if an error occurs!
//            /** @noinspection PhpUnhandledExceptionInspection */
//            $resultList = $this->getLegacyKernel()->runCallback(
//                function () use ( $fetchArray )
//                {
//                    /*
//                    return \eZSearch::search( $fetchArray, $fetchArray );
//                    //*/
//
//                    //*
//                    return eZFunctionHandler::execute(
//                        'content',
//                        'search',
//                        $fetchArray
//                    );
//                    //*/
//                }
//            );
//
//            $searchResult = array();
//            $searchResult['searchHits'] = $resultList['SearchResult'];
//            $searchResult['totalCount'] = $resultList['SearchCount'];



            /** @var $repository \eZ\Publish\API\Repository\Repository */
            $contentService = $repository->getContentService();
            $locationService = $repository->getLocationService();


            // https://docs.netgen.io/projects/site-api/en/latest/reference/services.html
            // netgen search service
            $netgenSiteService = $this->get( 'netgen.ezplatform_site.site' );

            $searchService = $netgenSiteService->getFindService();

            if ( is_object( $searchService ) )
            {
//                $searchService = $repository->getSearchService();
            }


            /** @var $configResolver \eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Configuration\ConfigResolver */
            $languages = $configResolver->getParameter('languages');

//            $location = $locationService->loadLocation(42);

            $location = $rootLocation;

            $queryString = $searchText;

            $query = new \eZ\Publish\API\Repository\Values\Content\LocationQuery();
            $criteria = [];

            if (null !== $queryString) {
                $query->query = new Criterion\FullText($queryString);
            }
//            if (null !== $section) {
//                $criteria[] = new Criterion\SectionId($section->id);
//            }
//            if (!empty($contentTypes)) {
//                $criteria[] = new Criterion\ContentTypeId(array_column($contentTypes, 'id'));
//            }
//            if (!empty($lastModified)) {
//                $criteria[] = new Criterion\DateMetadata(
//                    Criterion\DateMetadata::MODIFIED,
//                    Criterion\Operator::BETWEEN,
//                    [$lastModified['start_date'], $lastModified['end_date']]
//                );
//            }
//            if (!empty($created)) {
//                $criteria[] = new Criterion\DateMetadata(
//                    Criterion\DateMetadata::CREATED,
//                    Criterion\Operator::BETWEEN,
//                    [$created['start_date'], $created['end_date']]
//                );
//            }
//            if ($creator instanceof User) {
//                $criteria[] = new Criterion\UserMetadata(
//                    Criterion\UserMetadata::OWNER,
//                    Criterion\Operator::EQ,
//                    $creator->id
//                );
//            }

//            if (null !== $subtree) {
//                $criteria[] = new Criterion\Subtree($subtree);
//            }
//
            if (!empty($criteria)) {
                $query->filter = new Criterion\LogicalAnd($criteria);
            }

//            if (!$this->searchService->supports(SearchService::CAPABILITY_SCORING)) {
//                $query->sortClauses[] = new SortClause\DateModified(Query::SORT_ASC);
//            }

//            $pagerfanta = new Pagerfanta(
//                new ContentSearchAdapter($query, $this->searchService)
//            );
//
//            $pagerfanta->setMaxPerPage($limit);
//            $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));




//            $query = new LocationQuery([
//                'filter' => new LogcialAnd([
//                    new ParentLocationId($location->parentLocationId),
//                    new ContentTypeId($location->contentInfo->contentTypeId),
//                    new LogicalNot(
//                        new LocationId($location->id)
//                    ),
//                ]),
//            ]);


            // facets test
            // https://doc.ezplatform.com/en/latest/guide/search/search/#solr-bundle
            // https://doc.ezplatform.com/en/latest/api/public_php_api_search/#faceted-search

            // For full list and details of available Search Criteria, see [Search Criteria reference](../guide/search/search_criteria_reference.md).
            // https://github.com/ezsystems/developer-documentation/blob/master/docs/api/public_php_api_search.md

//            $facetSupport = $searchService->supports(SearchService::CAPABILITY_FACETS);

//            if ( $facetSupport )
//            {
//                $query->facetBuilders[] = new \eZ\Publish\API\Repository\Values\Content\Query\FacetBuilderUserFacetBuilder(
//                    [
//                        'name' => 'User',
//                        'type' => FacetBuilder\UserFacetBuilder::OWNER,
//                        'minCount' => 2,
//                        'limit' => 5
//                    ]
//                );
//            }




//            $now = new DateTime();
//            $yearAgo = new DateTime();
//            $yearAgo->modify('-1 year');

            $query->facetBuilders = [
                // Kaliop Facet Builders
//                new KaliopFacetBuilder\FieldRangeFacetBuilder([
//                    'name' => 'Numeric field range facet',
//                    'fieldPath' => 'product/price',
//                    'start' => 100,
//                    'end' => 500,
//                    'gap' => 50,
//                    'limit' => 8,
//                ]),
//                new KaliopFacetBuilder\DateRangeFacetBuilder([
//                    'name' => 'Date range facet',
//                    'fieldPath' => 'article/publication_date',
//                    'start' => $yearAgo,
//                    'end' => $now,
//                    'gap' => new DateInterval('P1M'),
//                    'limit' => 12,
//                ]),


                new FacetBuilder\UserFacetBuilder(
                    [
                        'name' => 'User',
                        'type' => FacetBuilder\UserFacetBuilder::OWNER,
                        'minCount' => 2,
                        'limit' => 5
                    ]),

                new FacetBuilder\SectionFacetBuilder(
                    [
                        'name' => 'Section',
                        'minCount' => 1,
                        'limit' => 5
                    ]),

                new FacetBuilder\ContentTypeFacetBuilder([
                    'name' => 'Content type facet',
                ]),

                // Base eZ Facet Builders
                new FacetBuilder\FieldFacetBuilder([
                    'name' => 'Simple field facet',
                    'fieldPaths' => 'title',
                    'limit' => 20,
                ]),
                new FacetBuilder\FieldFacetBuilder([
                    'name' => 'Object relation(s) facet',
                    'fieldPaths' => 'article/author/id',
                    'limit' => 20,
                ]),

                new FacetBuilder\CriterionFacetBuilder([
                    'name' => 'Criterion facet title contains test',
                    'filter' => new Criterion\Field('ng_news/title', Criterion\Operator::CONTAINS, 'test'),
                ]),
            ];




            $searchResult = $searchService->findLocations( $query, ['languages' => $languages] );



        }
//        else
//        {
//            $searchResult = array();
//            $searchResult['searchHits'] = array();
//            $searchResult['totalCount'] = 0;
//        }

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
