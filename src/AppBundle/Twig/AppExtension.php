<?php


namespace AppBundle\Twig;


use Cjw\FieldTypesBundle\FieldType\EzMatrix\Classes\EzMatrix;
//use eZ\Publish\API\Repository\Values\Content\Content;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Content;
//use eZ\Publish\API\Repository\Values\Content\Location;
use Netgen\EzPlatformSiteApi\Core\Site\Values\Location;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Cjw\PublishToolsBundle\Services\PublishToolsService
     */
    protected $PublishToolsService;

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    protected $globalHelper;


    /**
     * JacSiteSparkassenverbandBayernExtension constructor.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     * @param \Cjw\PublishToolsBundle\Services\PublishToolsService      $PublishToolsService
     * @param \eZ\Publish\Core\MVC\Symfony\Templating\GlobalHelper      $globalHelper
     */
    public function __construct( $container, $PublishToolsService, $globalHelper )
    {
        $this->container           = $container;
        $this->PublishToolsService = $PublishToolsService;
        $this->globalHelper        = $globalHelper;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction( 'create_price_matrix', [$this, 'createPriceMatrix'] )
        ];
    }

    public function getTests ()
    {
        return [
            new TwigTest( 'local_host', [$this, 'isLocalHost'])
        ];
    }

    /**
     * @param Location|int $locationId
     * @param null         $accommodationType
     *
     * @return array
     */
    public function createPriceMatrix( $locationId = null, $accommodationType = null )
    {
        // "Normalize" $location/$locationId
        if ( !\is_array( $locationId ) )
        {
            $location   = (\is_numeric( $locationId )) ?
                $this->PublishToolsService->fetchLocationListArr( [ [ $locationId ] ] )[ 0 ][ 0 ] :
                $locationId;
            $locationId = $location->id;
        }

        $seasons = $this->PublishToolsService->fetchLocationListArr( [[2]], [
            'depth' => 10,
            'include' => [ 'cjw_saison' ],
            'main_location_only' => true,
            'sortby' => [ 'LocationPriority' => 'ASC' ]
        ] )[0]['children'];

        if ( !empty( $seasons ) )
        {
            if ( $locationId )
            {
                $objects = $this->PublishToolsService->fetchLocationListArr( [ (array)$locationId ] )[0];
            }
        }
        else
        {
            if( $accommodationType )
            {
                $classAttributeFilter = [ [
                    'cjw_object_accommodation/accommodation_type',
                    '=',
                    $accommodationType
                ] ];
            }
            else
            {
                $classAttributeFilter = null;
            }

            $objects = $this->PublishToolsService->fetchLocationListArr( [[2]], [
                'depth' => 10,
                'parent_node_id' => 2,
                'include' => [ 'cjw_object_accommodation' ],
                'attribute_filter' => $classAttributeFilter,
                'sortby' => [ 'LocationPriority' => 'ASC' ]
            ] )[0]['children'];
        }

        if ( !empty( $objects ) )
        {
            $result = [];
            $temp = [];

            foreach( $objects as $index => $location )
            {
                /** @var Content $content */
//                $content = $this->PublishToolsService->loadContentById( $location->getContentInfo()->id );
                $content = $this->PublishToolsService->loadContentById( $location->contentInfo->id );

//                $fields = $content->getFields();
                $specialMatrixTitle = '';

                // Set table header
                if ( $content->hasField( 'table_header' ) )
                {
//                    $tableHeader = $content->getFieldValue( 'table_header' );
                    $tableHeader = $content->getField( 'table_header' );
                }
                else
                {
//                    $tableHeader = $content->getFieldValue( 'title' )->text;
                    $tableHeader = $content->getField( 'title' );
                }

                $result['header'][$location->id] = $tableHeader;

                // Set special matrix header
                if ( !empty( $specialMatrixTitle ) && $content->hasField( 'special_matrix_tile' ) && $content->getFieldValue(  'special_matrix_tiole' ) )
                {
                    $specialMatrixTitle = $content->getFieldValue( 'special_matrix_tile' );
                    $result['special_matrix_title'] = $specialMatrixTitle;
                }

                // Set matrix data and special matrix data per season
                foreach ( ['matrix', 'special_matrix'] as $fieldName )
                {
                    if ( $content->hasField( $fieldName ) && $content->getFieldValue( $fieldName ) )
                    {
                        /** @var EzMatrix $matrix */
                        $matrix = $content->getFieldValue( $fieldName );
                        $matrix = $matrix->matrix;
                        $matrix = $matrix[ 'rows' ][ 'sequential' ];

                        foreach ( $matrix as $item )
                        {
                            $columns  = $item[ 'columns' ];
                            $saisonId = $columns[ 0 ];
                            $val      = $columns[ 1 ];

                            $temp[ $location->id ][ $saisonId ] = $val;
                        }
                    }
                }

                if ( count( $temp ) )
                {
                    foreach ( $seasons as $season )
                    {
                        $content = $this->PublishToolsService->loadContentById( $season->contentInfo->id );
//                        $fields = $content->getFields();

                        $key = 'standard';

                        // Set id
                        if ( $content->hasField( 'id' ) && $content->getFieldValue( 'id' ) )
                        {
                            $id = $content->getFieldValue( 'id' )->text;

                            // Is special?
                            if ( $content->hasField( 'is_special' ) && $content->getFieldValue( 'is_special' )->bool == 1 )
                            {
                                $key = 'special';
                            }

                            // Set start
                            if ( $content->hasField( 'start' ) && $content->getFieldValue( 'start' ) )
                            {
                                $start = $content->getFieldValue( 'start' );
                                $start = $start->date;

                                // Set end
                                if ( $content->hasField( 'end' ) && $content->getFieldValue( 'end' ) )
                                {
                                    $end = $content->getFieldValue( 'end' );
                                    $end = $end->date;

                                    // Set public id
                                    $publicId = $content->getFieldValue( 'id_public' )->text;

                                    $result[ 'data' ][ $key ][ $id ] = [
                                        'start' => $start,
                                        'end' => $end,
                                        'public_id' => $publicId
                                    ];

                                    // Set object data per saison
                                    foreach ( $temp as $locationId => $item )
                                    {
                                        if ( isset( $item[ $id ] ) )
                                        {
                                            $result[ 'data' ][ $key ][ $id ][ 'content' ][ $locationId ] = $item[ $id ];
                                        }
                                        else
                                        {
                                            $result[ 'data' ][ $key ][ $id ][ 'content' ][ $locationId ] = 'a. Anfrage';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isLocalHost( $url )
    {
        $localUriParts = $this->globalHelper->getConfigResolver()->getParameter( 'system.local_uri_parts', 'cjwsite' );

        return self::stringContains( $url, $localUriParts );
    }

    ///=== Helper functions ===///
    /**
     * Checks if $haystack begins with $needle.
     *
     * @param string $haystack
     * @param string|string[] $needle
     * @return bool
     */
    public static function stringBeginsWith( $haystack, $needle )
    {
        if ( \is_array( $needle ) )
        {
            foreach ( $needle as $string )
            {
                if ( strncmp( $haystack, $string, strlen( $string ) ) === 0 )
                {
                    return true;
                }
            }

            return false;
        }

        return (strncmp( $haystack, $needle, strlen( $needle ) ) === 0);
    }

    /**
     * Checks if $needle is inside $haystack.
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return bool
     */
    public static function stringContains( $haystack, $needle )
    {
        if ( \is_array( $needle ) )
        {
            foreach ( $needle as $string )
            {
                if ( \strstr( $haystack, $string) !== false )
                {
                    return true;
                }
            }

            return false;
        }

        return (strstr( $haystack, $needle) !== false);
    }

    /**
     * Checks if $haystack ends with $needle.
     *
     * @param string $haystack
     * @param string|string[] $needle
     *
     * @return bool
     */
    public static function stringEndsWith( $haystack, $needle )
    {
        if ( \is_array( $needle ) )
        {
            foreach ( $needle as $string )
            {
                if ( strpos( $haystack, $string, strlen( $string ) ) === (strlen( $haystack ) - strlen( $string )) )
                {
                    return true;
                }
            }

            return false;
        }

        return (strpos( $haystack, $needle, strlen( $needle ) ) === (strlen( $haystack ) - strlen( $needle )));
    }
}
