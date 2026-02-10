<?php
/**
 * File containing the TwigFunctionsService class
 *
 * @copyright Copyright (C) 2007-2015 CJW Network - Coolscreen.de, JAC Systeme GmbH, Webmanufaktur. All rights reserved.
 * @license http://ez.no/licenses/gnu_gpl GNU GPL v2
 * @version //autogentag//
 * @filesource
 *
 */

namespace Cjw\PublishToolsBundle\Services;

use Netgen\EzPlatformSiteApi\Core\Site\Values\Location as NetgenLocation;
use eZ\Publish\API\Repository\Values\Content\Location as EzLocation;
use Symfony\Component\DependencyInjection\Container;

use eZ\Publish\API\Repository\Values\Content\Location;
use Netgen\EzPlatformSiteApi\API\Values\Location as SiteApiLocation;

use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Content as EzContent;
use Netgen\EzPlatformSiteApi\API\Values\Content as SiteApiContent;

class TwigFunctionsService extends \Twig\Extension\AbstractExtension
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Cjw\PublishToolsBundle\Services\PublishToolsService
     */
    protected $PublishToolsService;

    protected $TplVarsBufferArr;

    private $csrfProvider;

    /**
     * @param Container $container
     * @param \Cjw\PublishToolsBundle\Services\PublishToolsService $PublishToolsService
     */
    public function __construct( Container $container, $PublishToolsService )
    {
        $this->container = $container;
        $this->PublishToolsService = $PublishToolsService;
        $this->TplVarsBufferArr = array();
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction( 'cjw_cache_set_ttl', array( $this, 'setCacheTtl' ) ),
            new \Twig_SimpleFunction( 'cjw_breadcrumb', array( $this, 'getBreadcrumb' ) ),
            new \Twig_SimpleFunction( 'cjw_treemenu', array( $this, 'getTreemenu' ) ),
            new \Twig_SimpleFunction( 'cjw_load_content_by_id', array( $this, 'loadContentById' ) ),
            new \Twig_SimpleFunction( 'cjw_load_contentinfo_by_id', array( $this, 'loadContentInfoById' ) ),
            new \Twig_SimpleFunction( 'cjw_fetch_content', array( $this, 'fetchContent' ) ),
            new \Twig_SimpleFunction( 'cjw_user_get_current', array( $this, 'getCurrentUser' ) ),
            new \Twig_SimpleFunction( 'cjw_lang_get_default_code', array( $this, 'getDefaultLangCode' ) ),
            new \Twig_SimpleFunction( 'cjw_content_download_file', array( $this, 'streamFile' ) ),
            //new \Twig_SimpleFunction( 'cjw_content_stream_file', array( $this, 'streamFile' ) ),
            new \Twig_SimpleFunction( 'cjw_redirect', array( $this, 'redirect' ) ),
            new \Twig_SimpleFunction( 'cjw_get_content_type_identifier', array( $this, 'getContentTypeIdentifier' ) ),
            new \Twig_SimpleFunction( 'cjw_get_content_type_name', array( $this, 'getContentTypeName' ) ),
            new \Twig_SimpleFunction( 'cjw_template_get_var', array( $this, 'getTplVar' ) ),
            new \Twig_SimpleFunction( 'cjw_template_set_var', array( $this, 'setTplVar' ) ),
            new \Twig_SimpleFunction( 'cjw_file_exists', array( $this, 'fileExists' ) ),
            new \Twig_SimpleFunction( 'cjw_csrf_token_generate', array( $this, 'csrfTokenGenerate' ) ),
            new \Twig_SimpleFunction( 'cjw_hash_md5', array( $this, 'hashMd5' ) ),
            new \Twig_SimpleFunction( 'cjw_get_asset_path', array( $this, 'getAssetPath' ) ),
            new \Twig_SimpleFunction( 'cjw_add_timestamp_to_filepath', array( $this, 'addTimestampToFilepath' ) ),
            new \Twig_SimpleFunction( 'cjw_load_content_relations', array( $this, 'loadContentRelations' ) ),
            new \Twig_SimpleFunction( 'cjw_load_content_reverse_relations', array( $this, 'loadContentReverseRelations' ) ),
            new \Twig_SimpleFunction( 'cjw_load_content_location_list', array( $this, 'loadContentLocationList' ) ),

            new \Twig_SimpleFunction( 'cjw_is_request_versionview', array( $this, 'isRequestVersionView' ) ),

            new \Twig_SimpleFunction( 'cjw_load_content_list_from_relation_field', [ $this, 'loadContentListFromRelationField' ] ),
            new \Twig_SimpleFunction( 'cjw_get_class', [ $this, 'getClass' ] ),
            new \Twig_SimpleFunction( 'cjw_get_object_states_by_location', [ $this, 'getObjectStatesByLocation' ] ),
            new \Twig\TwigFunction( 'cjw_get_tags', [ $this, 'getTags' ] )
        );
    }


    public function getTests ()
    {
        return [
            new \Twig_SimpleTest( 'instanceof', function ( $var, $instance ) { return ($var instanceof $instance); }),
            new \Twig_SimpleTest( 'numeric', function ( $var ) { return \is_numeric( $var ); } )
        ];
    }

    /**
     * Returns a list of filters to add to the existing list
     *
     * @return array
     */
    public function getFilters()
    {
        return array();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'cjw_publishtools_twig_extension';
    }

    /**
     * example:  {{ cjw_cache_set_ttl( 0 ) }}
     *
     * the first call will be set the ttl of the template
     *
     * @param int $ttl ttl of http_cache in s, 0 http_cache off
     */
    public function setCacheTtl( $ttl = 0 )
    {
        if ( !isset( $GLOBALS['CJW_HTTP_CACHE_TTL'] ) )
        {
            $GLOBALS['CJW_HTTP_CACHE_TTL'] = (int) $ttl;
        }
    }

    /**
     * Returns the breadcrumb for $locationId
     *
     * @param integer $locationId
     * @param array $params
     *
     * @return array
     */
    public function getBreadcrumb( $locationId = 0, array $params = array() )
    {
        $pathArr = $this->PublishToolsService->getPathArr( $locationId, $params );
        return $pathArr;
    }

    /**
     * Returns an treemenu (list of locations) for $locationId
     *
     * @param integer|EzLocation|SiteApiLocation $locationId
     * @param array $params
     *
     * @return array
     */
    public function getTreemenu( $locationId = 0, array $params = array() )
    {
        // Get id from location object, if an object has been passed
        if ( \is_object( $locationId ) )
        {
            $locationId = $locationId->id;
        }

        $menuArr     = [];
        $fetchParams = [
            'depth'   => 1,
            'offset'  => 0,
            'include' => false,
            'datamap' => false,
            'sortby'  => false,
        ];

        if ( isset( $params['depth'] ) && $params['depth'] > 1 )
        {
            $fetchParams['depth'] = $params['depth'];
        }

        if ( isset( $params['offset'] ) && $params['offset'] > 0 )
        {
            $fetchParams['offset'] = $params['offset'];
        }

        if ( isset( $params['include'] ) && is_array( $params['include'] ) && count( $params['include'] ) > 0 )
        {
            $fetchParams['include'] = $params['include'];
        }

        if ( isset( $params['datamap'] ) && $params['datamap'] === true )
        {
            $fetchParams['datamap'] = $params['datamap'];
        }

        if ( isset( $params['sortby'] ) && $params['sortby'] !== false )
        {
            $fetchParams['sortby'] = $params['sortby'];
        }

        $fetchParams['priority'] = (!empty( $params['priority'] )) ?
            $params['priority'] : false;


        $pathArr = $this->PublishToolsService->getPathArr( $locationId, array( 'offset' => $fetchParams['offset'] ) );

        $depthCounter = 1;

        // reset offset param - use only in for first level fetch
        $fetchParams['offset'] = 0;

        foreach( $pathArr['items'] as $location )
        {
            $result = $this->PublishToolsService->fetchLocationListArr(
                array( $location['locationId'] ),
                $fetchParams
            );

            /** @var EzLocation[]|SiteApiLocation[] $insertArr */
            $insertArr = $result[$location['locationId']]['children'];

            // add first, last and level info
            $insertArrNew = array();
            $lastCounter = 0;
            $firstToggle = 1;
            foreach( $insertArr as $child )
            {
                $content = self::getContentFromLocation( $child );
                $name = $this->PublishToolsService->getContentName( $content );

                if ( $lastCounter > 0 )
                {
                    $firstToggle = 0;
                }

                $insertArrNew[] = [
                    'node'     => $child,
                    'location' => $child,
                    'content'  => $content,
                    'name'     => $name,
                    'level'    => $depthCounter,
                    'selected' => 0,
                    'children' => 0,
                    'first'    => $firstToggle,
                    'last'     => 0,
                ];
                $lastCounter++;

                unset( $content );
                unset( $name );
            }
            if( count( $insertArrNew ) )
            {
                $insertArrNew[$lastCounter-1]['last'] = 1;
            }

            // get insert position
            $insertPosition = 0;
            foreach( $menuArr as $insertKey => $menuItem )
            {
                if( $location['locationId'] == $menuItem['node']->id )
                {
                    // add selected and children count info
                    $menuArr[$insertKey]['selected'] = 1;
                    $menuArr[$insertKey]['children'] = count( $insertArrNew );

                    $insertPosition = $insertKey + 1;
                    break;
                }
            }

            // if no insert position found (location is not part of menu tree), show top menu entries only
            if ( $insertPosition > 0 || $depthCounter == 1 )
            {
                // http://stackoverflow.com/questions/3797239/insert-new-item-in-array-on-any-position-in-php
                array_splice( $menuArr, $insertPosition, 0, $insertArrNew );
            }

            $depthCounter++;
            if( $depthCounter > $fetchParams['depth'] )
            {
                break;
            }
        }

        return $menuArr;
    }

    /**
     * Fetch content by contentId.
     *
     * @param integer $contentId
     *
     * @return EzContent
     */
    public function loadContentById( $contentId, $languages = null )
    {
        $content = $this->PublishToolsService->loadContentById( $contentId, $languages );
        return $content;
    }

    /**
     * Load contentInfo by contentId.
     *
     * @param integer $contentId
     * @return \eZ\Publish\API\Repository\Values\Content\ContentInfo
     */
    public function loadContentInfoById( $contentId )
    {
        $contentInfo = $this->PublishToolsService->loadContentInfoById( $contentId );
        return $contentInfo;
    }

    /**
     * Returns / find a list of locations / content y search params
     *
     * @param array $locationId
     * @param array $params
     *
     * @return array
     */
    public function fetchContent( $locationId, array $params = array() )
    {
        $locationList = $this->PublishToolsService->fetchLocationListArr( $locationId, $params );
        return $locationList;
    }

    /**
     * Returns the current user info (is logged in)
     *
     * @return array
     */
    public function getCurrentUser()
    {
        $user = $this->PublishToolsService->getCurrentUser();
        return $user;
    }

    /**
     * Returns the default language code string
     *
     * @return string
     */
    public function getDefaultLangCode()
    {
        $lang = $this->PublishToolsService->getDefaultLangCode();
        return $lang;
    }

    /**
     * Send location header to redirect the client to the given URL.
     *
     * @param string $url URL where the client should be redirected to.
     */
    public function redirect( $url = "" )
    {
        if( $url )
        {
            header( 'Location: '.$url );
            exit();
        }
    }

    /**
     * Streams the given file directly to the client.
     *
     * @param bool $file fileobject or filepath
     * @param string $contentDisposition inline or attachment
     * @param int $maxAge cachetime
     */
    /*public function streamFile( $file = false, $contentDisposition = 'inline', $maxAge = 2592000 )*/
    public function streamFile( $file = false, $contentDisposition = 'inline', $maxAge = 0, $fileName = '' )
    {
        if ( $file )
        {

            if ( is_object( $file ) )
            {
                $filepath = "{$this->PublishToolsService->getStorageDir()}/original/{$file->id}";
                if ( $fileName == '' )
                    $fileName = $file->fileName;
            }
            else
            {
                $filepath = $file;
            }

            $fileStream = new FileStreamService( $filepath, $contentDisposition, (int) $maxAge, $fileName );
            $fileStream->start();

            // $filepath = ".{$file->uri}";
            // $fileStream = new CjwFileStream( $filepath );
        }
    }

    /**
     * Download the given file directly to the client.
     *
     * @param mixed  $file
     * @param string $contentDisposition
     */
//    public function downloadFile( $file = false, $contentDisposition = 'attachment' )
//    {
//        if ($file) {
//            $filepath = ".{$file->uri}";
//            /* Check whether there is a `mimeType` property in the $file
//               object. If not we're trying to determine it by using Symfony's
//               MimeTypeGuesser! */
//            if (isset($file->mimeType)) {
//                $mimeType = $file->mimeType;
//            } else {
//                // Guess the mime type by using Symfony's MimeTypeGuesser class
//                $guesser = MimeTypeGuesser::getInstance();
//                $mimeType = $guesser->guess($filepath);
//            }
//
//            header('Content-Description: File Transfer');
//            header("Content-type: {$mimeType}");
//            header('Content-Transfer-Encoding: binary');
//            header('Content-Length: ' . $file->fileSize);
//            header('Content-Disposition: ' . $contentDisposition . '; filename=' . urlencode($file->fileName));
//            ob_clean();
//            flush();
//
//            readfile($filepath);
//
//            exit;
//        }
//    }



    /**
     * Gets the {@see ContentType}'s identifier by it's ID.
     *
     * ```twig
     * {{ cjw_get_content_type_identifier( content.contentInfo.contentTypeId )  }}
     *
     * e.g
     * {% if cjw_get_content_type_identifier( content.contentInfo.contentTypeId ) == 'cjw_gallery' ) %}
     * ```
     *
     * @param  integer $contentTypeId
     * @return bool
     */
    public function getContentTypeIdentifier( $contentTypeId )
    {
        return $this->PublishToolsService->getContentTypeIdentifier( $contentTypeId );
    }

    /**
     * Gets the {@see ContentType}'s name by it's ID.
     *
     * ```twig
     * {{ cjw_get_content_type_identifier( content.contentInfo.contentTypeId )  }}
     *
     * e.g
     * {% if cjw_get_content_type_identifier( content.contentInfo.contentTypeId ) == 'CJW Galerie' ) %}
     * ```
     *
     * @param  integer $contentTypeId
     * @return bool
     */
    public function getContentTypeName( $contentTypeId )
    {
        return $this->PublishToolsService->getContentTypeName( $contentTypeId );
    }

    /**
     * Returns a global template variable, which has been saved via the
     * {@see TwigFunctionsService::setTplVar} function.
     *
     * @param bool|string $var Name of the variable, which should be fetched from the buffer,
     *                         defaults to false if not given.
     * @return bool False if no variable with the given variable name could be found in the buffer,
     *              or the requested variable's value.
     */
    public function getTplVar( $var = false )
    {
        $result = false;

        if ( $var !== false && isset( $this->TplVarsBufferArr[$var] ) !== false )
        {
            $result = $this->TplVarsBufferArr[$var];
        }

        return $result;
    }

    /**
     * Sets a global template variable, which can be fetched via the
     * {@see TwigFunctionsService::getTplVar} function.
     *
     * @param bool|string $var Name of the variable, which should be set in the buffer, defaults to
     *                         false if not given.
     * @param mixed $value Value of the variable, which should be set in the buffer, defaults to
     *                         false if not given.
     *
     * @return bool|void
     */
    public function setTplVar( $var = false, $value = false )
    {
        if ( $var === false || $value === false )
        {
            return false;
        }

        $this->TplVarsBufferArr[$var] = $value;
// ToDo: returns visible 1 in tpl, use if cjw_template_get_var for testing?
//        return true;
    }

    /**
     * Checks if a file exists
     */
    public function fileExists( $file = '' )
    {

        $result = false;

        if ( file_exists( $file ) )
        {
            $result = true;
        }

        return $result;
    }

    /*
     * @deprecated
     * see: http://symfony.com/doc/current/reference/twig_reference.html#csrf-token
     */
    public function csrfTokenGenerate( $type = 'authenticate' )
    {
        $this->csrfProvider = $this->container->get( 'form.csrf_provider' );
        $csrfToken = $this->csrfProvider->generateCsrfToken( $type );

        return $csrfToken;
    }

    public function hashMd5( $string = '' )
    {
        return md5( $string );
    }

    /**
     * Returns the true asset path, which can be used directly for accessing
     * a bundle's assets in a template.
     *
     * @see PublishToolsService::getAssetPath()
     * @param string $subPath [OPTIONAL]
     *
     * @return string
     */
    public function getAssetPath( $subPath = "" )
    {
        return $this->PublishToolsService->getAssetPath( $subPath );
    }

    /**
     * Appends the modification timestamp of the file passed to the filepath.
     *
     * ## Example
     *
     * **Code**
     * ```twig
     *     {% set filepath_with_timestamp = cjw_add_timestamp_to_filepath( filepath ) %}
     *
     *     {{ filepath_with_timestamp }}
     * ```
     *
     * **Output**
     * ```
     * %FILEPATH%?ts=321324657
     * ```
     *
     *
     * @param string $filepath The filepath's root directory has to be the `web`
     *                         directory with leading slash character, e.g.
     *                         "/bundles/jacsitebundle/js/site.js".
     *
     * @param string $paramName [OPTIONAL] Name of the GET parameter to use for
     *                          the timestamp; defaults to "ts".
     *
     * @return bool|string The filepath passed including the file's modification
     *                     timestamp, or boolean false if the file doesn't
     *                     exist.
     */
    public function addTimestampToFilepath( $filepath, $paramName = "ts" )
    {
        if ( file_exists( ".$filepath" ) )
        {
            return $filepath . "?$paramName=" . filemtime( ".$filepath" );
        }

        return false;
    }

    /**
     * Loads all content relations of the passed $contentOrVersionInfo object.
     *
     * @param int|Content|VersionInfo|Location|ContentInfo $objectOrId
     * @param bool $asContent If true the resulting {@see Relation} list will be
     *                        converted to a list of ready-to-use {@see Content}
     *                        objects.
     *
     * @return Relation[]|Content[] A list of {@see Relation} objects, if
     *             $asContent has been set to `false`, a list of {@see Content}
     *             object if $asContent is set to `true`.
     *
     * @throws UnauthorizedException
     */
    public function loadContentRelations( $objectOrId, $asContent = true )
    {
        return $this->PublishToolsService->loadContentRelations( $objectOrId, $asContent );
    }

    /**
     * Loads all content reverse relations of the passed $contentOrVersionInfo
     * object.
     *
     * @param int|Content|VersionInfo|Location|ContentInfo $objectOrId
     * @param bool $asContent If true the resulting {@see Relation} list will be
     *                        converted to a list of ready-to-use {@see Content}
     *                        objects.
     *
     * @return Relation[]|Content[] A list of {@see Relation} objects, if
     *             $asContent has been set to `false`, a list of {@see Content}
     *             object if $asContent is set to `true`.
     *
     * @throws UnauthorizedException
     */
    public function loadContentReverseRelations( $objectOrId, $asContent = true )
    {
        return $this->PublishToolsService->loadContentReverseRelations( $objectOrId, $asContent );
    }

    /**
     * @todo Documentation …
     *
     * @param array $relationList
     *
     * @return EzContent[]|SiteApiContent[]
     */
    public function loadContentListFromRelationField( $relationList )
    {
        return $this->PublishToolsService->getContentListFromRelationField( $relationList );
    }

    /**
     * Loads all locations which correspond to the passed $objectOrID.
     *
     * @param int|Content|VersionInfo|Location|ContentInfo $objectOrID
     *
     * @return Location[]
     * @throws \eZ\Publish\API\Repository\Exceptions\BadStateException
     */
    public function loadContentLocationList( $objectOrID )
    {
        return $this->PublishToolsService->loadContentLocationList( $objectOrID );
    }

    /**
     * Check whether a request uri seems to be a version view request or not.
     *
     * @param string $requestUri
     *
     * @return bool
     */
    public function isRequestVersionView( $requestUri )
    {
        return ( \strpos( $requestUri, '/content/versionview/' ) !== false );
    }

    /**
     * @param EzLocation|SiteApiLocation $location
     *
     * @return EzContent|SiteApiContent
     */
    private static function getContentFromLocation( $location )
    {
        $content = null;

        if ( $location instanceof EzLocation )
        {
            $content = $location->getContent();
        }
        else if( $location instanceof SiteApiLocation )
        {
            $content = $location->content;
        }

        return $content;
    }

    /**
     * @param EzLocation|SiteApiLocation $location
     *
     * @return int
     */
    private static function getContentIdFromLocation( $location )
    {
        $content = null;

        if ( $location instanceof EzLocation )
        {
            $content = $location->getContent()->id;
        }
        else if ( $location instanceof SiteApiLocation )
        {
            $content = $location->content->id;
        }

        return $content;
    }

    public static function getClass( $object )
    {
        return (new \ReflectionClass($object))->getShortName();
    }

    /**
     * @param EzLocation|SiteApiLocation $location
     *
     * @return array
     */
    public function getObjectStatesByLocation( $location )
    {
        $states = array();

        if ( $location instanceof EzLocation || $location instanceof NetgenLocation )
        {
            $content = self::getContentFromLocation( $location );

            $states = $this->PublishToolsService->getObjectStatesByContent( $content );
        }

        return $states;
    }

    /**
     * Fetch tags by parent tag id, if null, than all tags.
     *
     * @param int|null $parentTagId
     * @return \Netgen\TagsBundle\API\Repository\Values\Tags\Tag[]
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     * @throws \eZ\Publish\API\Repository\Exceptions\UnauthorizedException
     */
    public function getTags( $parentTagId = null )
    {
        return $this->PublishToolsService->getTags( $parentTagId );
    }
}

