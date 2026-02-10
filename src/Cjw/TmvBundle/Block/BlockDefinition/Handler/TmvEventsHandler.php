<?php

declare(strict_types=1);

namespace Cjw\TmvBundle\Block\BlockDefinition\Handler;

use eZ\Publish\API\Repository\Values\ValueObject;
use Netgen\Layouts\API\Values\Block\Block;
use Netgen\Layouts\Block\BlockDefinition\BlockDefinitionHandler;
use Netgen\Layouts\Block\DynamicParameters;
use Netgen\Layouts\Collection\QueryType\QueryTypeHandlerInterface;
use Netgen\Layouts\Parameters\ParameterBuilderInterface;
use Netgen\Layouts\Parameters\ParameterType;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\ContentInfo;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\Persistence\Database\DatabaseHandler;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


final class TmvEventsHandler extends BlockDefinitionHandler
{
    /**
     * @var \eZ\Publish\API\Repository\SearchService
     */
    private $searchService;

    /**
     * @var \eZ\Publish\API\Repository\Repository
     */
    protected $repository;

    /**
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @var \eZ\Publish\Core\Persistence\Database\DatabaseHandler
     */
    protected $databaseHandler;

    /**
     * @var array
     */
    public $listAllPlaces;

    /**
     * @var array
     */
    public $listAllCategories;

    /**
     * @var \eZ\Publish\API\Repository\Values\Content\Location
     */
    public $eventContainer;

    /**
     * @var Request
     */
    public $request;

    /**
     * @var array
     */
    public $filterParams = array();

    /**
     * TmvEventsHandler constructor.
     *
     * Params are defined in services.yml!
     *
     * @param Repository $repository
     * @param DatabaseHandler $databaseHandler
     * @param RequestStack $request
     */
    public function __construct( Repository $repository, DatabaseHandler $databaseHandler, RequestStack $request )
    {
        $this->request           = $request->getCurrentRequest();
        $this->repository        = $repository;
        $this->searchService     = $this->repository->getSearchService();
        $this->locationService   = $this->repository->getLocationService();
        $this->databaseHandler   = $databaseHandler;
        $this->listAllPlaces     = $this->fetchAllPlaces();
        $this->filterParams      = $this->setFilterParams();
    }

    /**
     * Set params for netgen layouts.
     *
     * @param ParameterBuilderInterface $builder
     */
    public function buildParameters(ParameterBuilderInterface $builder): void
    {
        $builder->add('parent', ParameterType\ItemLinkType::class);
        $builder->add('limit', ParameterType\IntegerType::class);

        $builder->add(
            'view_type',
            ParameterType\ChoiceType::class,
            [
                'required' => true,
                'options' => [
                    'List' => 'list',
                    'Grid' => 'grid'
                ]
            ]
        );

        $builder->add('number_of_columns', ParameterType\IntegerType::class);
        $builder->add('exclude_categories', ParameterType\TextLineType::class);
    }

    /**
     * Get params from netgen layouts, works with them and returns they to the templates.
     *
     * @param DynamicParameters $params
     * @param Block $block
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function getDynamicParameters(DynamicParameters $params, Block $block): void
    {
        $listEvents   = array();

        $parent            = $block->getParameter('parent');
        $limit             = (int) $block->getParameter('limit')->getValue();
        $numberOfColumns   = (int) $block->getParameter('number_of_columns')->getValue();
        $excludeCategories = $block->getParameter('exclude_categories')->getValue();
        $viewType          = $block->getParameter('view_type')->getValue();

        if ( is_string( $excludeCategories ) )
        {
            $excludeCategories = explode( ',', $excludeCategories );
        }
        else
        {
            $excludeCategories = array();
        }

        if ( $numberOfColumns == 0 )
        {
            $numberOfColumns = 1;
        }

        if ( !$parent->isEmpty() )
        {
            // value: ezlocation://1234
            $parentLocation   = explode( '//', $parent->getValue() );
            $parentLocationId = (int) $parentLocation[ 1 ];

            $this->eventContainer = $this->locationService->loadLocation( $parentLocationId );

            // frontend fetch
            $listEvents = $this->fetchEventsByFilter( $limit, 0, $excludeCategories );

            // backend fetch
            //$listEvents = $this->fetchEvents( $limit );

            $this->listAllCategories = $this->fetchAllCategories();
        }

        $params[ 'list_events' ]        = $listEvents;
        $params[ 'parent' ]             = $this->eventContainer;
        $params[ 'limit' ]              = $limit;
        $params[ 'number_of_columns' ]  = $numberOfColumns;
        $params[ 'exclude_categories' ] = $excludeCategories;
        $params[ 'view_type' ]          = $viewType;
        $params[ 'list_categories' ]    = $this->listAllCategories;
        $params[ 'list_places' ]        = $this->listAllPlaces;
        $params[ 'filter_params' ]      = $this->filterParams;
    }

    public function isContextual(Block $block): bool
    {
        return false;
    }

    /**
     * Basic fetch for tmv_events. No filter logic.
     *
     * @param int $limit
     * @return array
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function fetchEvents( $limit = 100 )
    {
        $resultQuery = array();
        $listEvents  = array();

        $filter   = array();
        $filter[] = new Criterion\ParentLocationId( $this->eventContainer->id );
        $filter[] = new Criterion\ContentTypeIdentifier( 'tmv_event' );

        $query = new LocationQuery( array( 'limit' => $limit ) );
        $query->filter = new Criterion\LogicalAnd( $filter );

        $resultQuery = $this->searchService->findLocations( $query );

        foreach ( $resultQuery->searchHits as $searchItem )
        {
            $listEvents[] = $searchItem->valueObject;
        }

        return $listEvents;
    }

    /**
     * Fetches dates and their parent events. Use filters by user input.
     *
     * @param int   $limit
     * @param int   $offset
     * @param array $excludedCategories
     * @return array
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function fetchEventsByFilter( $limit = 100, $offset = 0, $excludedCategories = array() )
    {

//        $listEvents = $this->loadFromCache();
//        if ( $listEvents ) {
//            return $listEvents;
//
//        }
        $listEvents = array();
        if ( $this->eventContainer instanceof Location )
        {
            //
            // fetch events by category, place and keyword
            //

            $filterEvents   = array();
            $filterEvents[] = new Criterion\ContentTypeIdentifier('tmv_event' );
            //$filterEvents[] = new Criterion\LocationId( $filterEventIds );

            $skipEventsWithMultipleDates = false;
            if ( isset( $this->filterParams[ 'hide_events_with_multiple_dates' ] ) && $this->filterParams[ 'hide_events_with_multiple_dates' ] )
            {
                $skipEventsWithMultipleDates = true;
            }

            if ( count( $this->filterParams[ 'places' ] ) != 0 )
            {
                $filterEvents[] = new Criterion\Field('place', Criterion\Operator::IN, $this->filterParams[ 'places' ] );
            }

            if ( count( $this->filterParams[ 'categories' ] ) != 0 && !in_array( 'all', $this->filterParams[ 'categories' ] ) )
            {
                $categoryFilter = array();

                foreach ( $this->filterParams[ 'categories' ] as $category )
                {
                    $categoryFilter[] = new Criterion\Field( 'categories', Criterion\Operator::LIKE, $category );
                    $categoryFilter[] = new Criterion\Field( 'categories', Criterion\Operator::LIKE, '*-' .$category );
                    $categoryFilter[] = new Criterion\Field( 'categories', Criterion\Operator::LIKE, $category . '-*' );
                }

                $filterEvents[] = new Criterion\LogicalOr( $categoryFilter );
            }

            if ( count( $excludedCategories ) != 0 )
            {
                foreach ( $excludedCategories as $excludedCategoryId )
                {
                    $excludedCategoryId = (int) trim( $excludedCategoryId );

                    if ( !in_array( $excludedCategoryId, $this->filterParams[ 'categories' ] ) )
                    {
                        $excludeCategoryCriterion = new Criterion\Field( 'categories', Criterion\Operator::LIKE, $excludedCategoryId );
                        $filterEvents[] = new Criterion\LogicalNot( $excludeCategoryCriterion );

                        $excludeCategoryCriterion = new Criterion\Field( 'categories', Criterion\Operator::LIKE, '*-' .$excludedCategoryId );
                        $filterEvents[] = new Criterion\LogicalNot( $excludeCategoryCriterion );

                        $excludeCategoryCriterion = new Criterion\Field( 'categories', Criterion\Operator::LIKE, $excludedCategoryId . '-*' );
                        $filterEvents[] = new Criterion\LogicalNot( $excludeCategoryCriterion );
                    }
                }
            }

            if ( $this->filterParams[ 'keyword' ] != '' )
            {
                $filterEvents[] = new Criterion\FullText( $this->filterParams[ 'keyword' ] );
            }

            //$queryEvents = new LocationQuery();
            $queryEvents         = new LocationQuery( array( 'offset' => $offset, 'limit' => 1000 ) );
            $queryEvents->filter = new Criterion\LogicalAnd( $filterEvents );

            $resultQueryEvents = $this->searchService->findLocations( $queryEvents );

            $listEventLocations = array();

            if ( $resultQueryEvents->totalCount != 0 )
            {
                $resultArray = array();
                foreach ( $resultQueryEvents->searchHits as $index => $searchItem )
                {
                    $eventLocation = $searchItem->valueObject;

                    $listEventLocations[ $eventLocation->id ] = $eventLocation;
                }
            }

            //$listEvents[ 'list_events' ] = $listEventLocations;
            $listEvents[ 'list_events' ] = array();

            //
            // fetch event dates by event location ids and date periods
            //

            if ( count( $listEventLocations ) > 0 )
            {
                $filterEventDates = array();
                $filterEventDates[] = new Criterion\Subtree( $this->eventContainer->pathString );
                $filterEventDates[] = new Criterion\ContentTypeIdentifier('tmv_date');

                // filter by parent event location ids
                $filterEventDates[] = new Criterion\ParentLocationId( array_keys( $listEventLocations ) );

                $startDateTime = null;
                if ( $this->filterParams[ 'start' ] != '' )
                {
                    $startDateTime = trim( $this->filterParams[ 'start' ] );

                    // add time if not exist
                    if ( strlen( $startDateTime ) <= 10 )
                    {
                        $startDateTime .= ' 00:00:01';
                    }
                }

                $endDateTime = null;
                if ( $this->filterParams[ 'end' ] != '' )
                {
                    $endDateTime = trim( $this->filterParams[ 'end' ] );

                    // add time if not exist
                    if ( strlen( $endDateTime ) <= 10 )
                    {
                        $endDateTime .= ' 23:59:59';
                    }
                }

                if ( $startDateTime !== NULL && $endDateTime !== NULL )
                {
                    $dateFilter   = array();
                    $dateFilter[] = new Criterion\Field( 'start', Criterion\Operator::GTE, strtotime( $startDateTime ) );
                    $dateFilter[] = new Criterion\Field( 'end', Criterion\Operator::LTE, strtotime( $endDateTime ) );

                    $filterEventDates[] = new Criterion\LogicalAnd( $dateFilter );
                }
                elseif ( $startDateTime !== NULL )
                {
                    $filterEventDates[] = new Criterion\Field( 'start', Criterion\Operator::GTE, strtotime( $startDateTime ) );
                }
                elseif ( $endDateTime !== NULL )
                {
                    $filterEventDates[] = new Criterion\Field( 'end', Criterion\Operator::LTE, strtotime( $endDateTime ) );
                }

                $queryEventDates              = new LocationQuery( array( 'offset' => $offset, 'limit' => 1000 ) );
                //$queryEventDates              = new LocationQuery();
                $queryEventDates->sortClauses = [ new SortClause\Field('tmv_date', 'start', QUERY::SORT_ASC) ];
                $queryEventDates->filter      = new Criterion\LogicalAnd( $filterEventDates );

                $resultQueryEventDates = $this->searchService->findLocations( $queryEventDates );

                if ( $resultQueryEventDates->totalCount != 0 )
                {
                    $listTempEventDateCount                    = array();
                    $listEventDateLocationIdsByEventLocationId = array();
                    $listEventDatesToRemoveByEventLocationId   = array();

                    foreach ( $resultQueryEventDates->searchHits as $index => $searchItem )
                    {
                        $eventDateLocation     = $searchItem->valueObject;
                        $eventParentLocationId = $eventDateLocation->parentLocationId;

                        $eventParentLocation = null;
                        if ( isset( $listEvents[ $eventParentLocationId ] ) )
                        {
                            $eventParentLocation = $listEvents[ $eventParentLocationId ];
                        }

                        /*
                        if ( isset( $listEvents[ 'list_event_dates' ][ $eventParentLocationId ] ) )
                        {
                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ][ 'list_date_locations' ][] = $eventDateLocation;
                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ][ 'last_date_location' ]    = $eventDateLocation;

                            if ( !in_array( $eventParentLocationId, $listEventLocationsWithMultipleDates ) && $skipEventsWithMultipleDates )
                            {
                                $listEventLocationsWithMultipleDates[] = $eventParentLocationId;
                            }
                        }
                        else
                        {
                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ] = array( 'event_location'      => $eventParentLocation,
                                                                                                 'list_date_locations' => array(),
                                                                                                 'first_date_location' => null,
                                                                                                 'last_date_location'  => null
                            );

                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ][ 'list_date_locations' ][] = $eventDateLocation;
                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ][ 'first_date_location' ]   = $eventDateLocation;
                            $listEvents[ 'list_event_dates' ][ $eventParentLocationId ][ 'last_date_location' ]    = $eventDateLocation;
                        }*/

                        $listEventDateLocationIdsByEventLocationId[ $eventParentLocationId ][] = $eventDateLocation->id;

                        if ( !isset( $listTempEventDateCount[ $eventParentLocationId ] ) )
                        {
                            $listEvents[ 'list_event_dates' ][ $eventDateLocation->id ] = array( 'date_location' => $eventDateLocation, 'event_location_id' => $eventParentLocationId );

                            $listTempEventDateCount[ $eventParentLocationId ] = 1;
                        }
                        else
                        {
                            $listTempEventDateCount[ $eventParentLocationId ]++;

                            if ( $listTempEventDateCount[ $eventParentLocationId ] == 3 && !in_array( $eventParentLocationId, $listEventDatesToRemoveByEventLocationId ) )
                            {
                                $listEventDatesToRemoveByEventLocationId[] = $eventParentLocationId;
                            }
                        }

                        if ( isset( $listEvents[ 'list_events' ][ $eventParentLocationId ] ) === FALSE )
                        {
                            $listEvents[ 'list_events' ][ $eventParentLocationId ] = array( 'location'            => $eventParentLocation,
                                                                                            'list_date_locations' => array( $eventDateLocation ),
                                                                                            'first_date_location' => $eventDateLocation,
                                                                                            'last_date_location'  => $eventDateLocation
                            );
                        }
                        else
                        {
                            $listEvents[ 'list_events' ][ $eventParentLocationId ][ 'list_date_locations' ][] = $eventDateLocation;
                            $listEvents[ 'list_events' ][ $eventParentLocationId ][ 'last_date_location' ]    = $eventDateLocation;
                        }
                    }

                    if ( $skipEventsWithMultipleDates && isset( $listEventDatesToRemoveByEventLocationId[ 0 ] ) )
                    {
                        foreach ( $listEventDatesToRemoveByEventLocationId as $eventLocationId )
                        {
                            if ( isset( $listEventDateLocationIdsByEventLocationId[ $eventLocationId ] ) )
                            {
                                foreach ( $listEventDateLocationIdsByEventLocationId[ $eventLocationId ] as $eventDateLocationId )
                                {
                                    if ( isset( $listEvents[ 'list_event_dates' ][ $eventDateLocationId ] ) )
                                    {
                                        unset( $listEvents[ 'list_event_dates' ][ $eventDateLocationId ] );
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
//
//        $this->writeToCache( $listEvents );
//        echo "<pre>";
//        var_dump($listEvents); echo "</pre>";die();
        return $listEvents;
    }

    /**
     * Fetch all existing categorie content objects.
     *
     * @return array
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException
     */
    public function fetchAllCategories()
    {
        $list = array();

        if ( $this->eventContainer instanceof Location )
        {
            $filter = array();
            $filter[] = new Criterion\Subtree( $this->eventContainer->pathString );
            $filter[] = new Criterion\ContentTypeIdentifier('tmv_categorie' );

            $query = new LocationQuery();
            $query->filter = new Criterion\LogicalAnd( $filter );

            $result = $this->searchService->findLocations( $query );

            foreach ( $result->searchHits as $searchItem )
            {
                $category        = $searchItem->valueObject;
                $categoryContent = $category->getContent();
                $categoryId      = (int) $categoryContent->getFieldValue( 'id' )->__toString();
                $categoryTitle   = $categoryContent->getFieldValue( 'title' )->__toString();

                $list[ $categoryId ] = $categoryTitle;
            }

            asort( $list, SORT_STRING | SORT_FLAG_CASE );
        }

        return $list;
    }

    /**
     * Fetch existing places by place attribute of tmv_event.
     *
     * @return array
     */
    public function fetchAllPlaces()
    {
        $query = $this->databaseHandler->createSelectQuery();
        $query->selectDistinct( 'ezcontentobject_attribute.data_text' )
            ->from( $this->databaseHandler->quoteTable( 'ezcontentobject_attribute' ) )
            ->from( $this->databaseHandler->quoteTable( 'ezcontentclass' ) )
            ->from( $this->databaseHandler->quoteTable( 'ezcontentclass_attribute' ) )
            ->where(
                $query->expr->lAnd(
                    $query->expr->eq( $this->databaseHandler->quoteColumn( 'identifier', 'ezcontentclass' ), $query->bindValue( 'tmv_event', null, \PDO::PARAM_STR ) ),
                    $query->expr->eq( $this->databaseHandler->quoteColumn( 'contentclassattribute_id', 'ezcontentobject_attribute' ), $this->databaseHandler->quoteColumn( 'id', 'ezcontentclass_attribute' ) ),
                    $query->expr->lAnd(
                        $query->expr->eq( $this->databaseHandler->quoteColumn( 'id', 'ezcontentclass' ), $this->databaseHandler->quoteColumn( 'contentclass_id', 'ezcontentclass_attribute' ) ),
                        $query->expr->eq( $this->databaseHandler->quoteColumn( 'identifier', 'ezcontentclass_attribute' ), $query->bindValue( 'place', null, \PDO::PARAM_STR ) )
                    )
                )
            )
            ->groupBy( $this->databaseHandler->quoteColumn( 'data_text', 'ezcontentobject_attribute' ) )
            ->orderBy( $this->databaseHandler->quoteColumn( 'data_text', 'ezcontentobject_attribute' ) )
        ;

        $result = $query->prepare();
        $result->execute();

        $result = $result->fetchAll();

        $list = array();

        if ( isset( $result[ 0 ] ) )
        {
            foreach ( $result as $item )
            {
                $list[] = $item[ 'data_text' ];
            }
        }

        return $list;
    }

    /**
     * Get user filter params and save it in global array.
     *
     * @return array
     */
    public function setFilterParams()
    {
        $keyword = '';
        if ( $this->request->get( 'Keyword' ) != '' )
        {
            $keyword = strip_tags( trim( $this->request->get( 'Keyword' ) ) );
        }

        $start = '';
        if ( $this->request->get( 'Start' ) != '' )
        {
            $start = strip_tags( trim( $this->request->get( 'Start' ) ) );
        }

        $end = '';
        if ( $this->request->get( 'End' ) != '' )
        {
            $end = strip_tags( trim( $this->request->get( 'End' ) ) );
        }

        $categories = array();
        if ( $this->request->get( 'Categories' ) != '' )
        {
            $categories = (array) $this->request->get( 'Categories' );
        }

        $places = array();
        if ( $this->request->get( 'Places' ) != '' )
        {
            $places = (array) $this->request->get( 'Places' );
        }

        $hideEventsWithMultipleDates = false;
        if ( $this->request->get( 'HideEventsWithMultipleDates' ) != '' )
        {
            $hideEventsWithMultipleDates = (bool) $this->request->get( 'HideEventsWithMultipleDates' );
        }

        return array( 'keyword' => $keyword, 'start' => $start, 'end' => $end, 'categories' => $categories, 'places' => $places, 'hide_events_with_multiple_dates' => $hideEventsWithMultipleDates );
    }

    /**
     * @param $timeout int|false
     * @param $cacheDir string
     * @param $cacheFileName string
     *
     * @return false|mixed|void
     */
    public function loadFromCache($timeoutMinutes = 10, $cacheDir = "var/site/storage/tmvcache", $cacheFileName = "listEvents.save" ) {//TODO ini
        $useCache  = false;
//        $cacheDir  = eZDir::path( array( eZSys::cacheDirectory(), $this->_cacheSubDir ) );

        $cachePath = $cacheDir."/".$cacheFileName;


        if ( (int) $timeoutMinutes <= 0 or $timeoutMinutes === false )
            $timeoutMinutes = 0;

        if ( !file_exists( $cacheDir ) )
        {

            if ( !mkdir( $cacheDir, 0777, true ) )
            {

                return false;
            }
        }


        if ( file_exists( $cachePath ) )
        {

            if ( filemtime( $cachePath ) + 60 * $timeoutMinutes >= time() )

            {
                $useCache = true;
            }
        }

        if ( ! $useCache )
        {
            return false;

        }

        else
        {
            if (file_exists($cachePath)){
                $objData = file_get_contents($cachePath);
                $data = unserialize($objData);
                if (!empty($data)){

                    return $data;
                }
                else return false;
            }
        }
    }

    /**
     * @param $data array
     * @param $cacheDir string
     * @param $cacheFileName string
     * @return bool|void
     */
    public function writeToCache ( $data, $cacheDir = "var/site/storage/tmvcache", $cacheFileName = "listEvents.save" ) {

        $objData = serialize( $data);
        $cachePath = $cacheDir."/".$cacheFileName;
        if ( !file_exists( $cacheDir ) )
        {
            if ( !mkdir( $cacheDir, 0777, true ) )
            {

                return false;
            }
        }

        if ( !file_put_contents( $cachePath , $objData  ) ) {//utf8_decode
            var_dump( "Couldn't create File, perhaps wrong permissions", "eZINI" );

            return false;
        }

        else return false;

    }
}
