# CjwPublishTools Refactoring

Generell

* Unterscheiden Services und Twig Extensions
* englische Bezeichnungen
* Code Standards


CjwPublishForm


CjwMobileDetection -> über composer.json jeweils aktuellste Version holen, nur einen Wrapper bereitstellen

CjwTheme


CjwPublishSearch
    aktuell mittels Legacy Wrapper
    bereit für Solr, etc..

    
CjwPublishSitemap
    Standard Search Services verwenden, eigenes Caching notwendig?
    
   
CjwPublishLegacyWrapper (emuliert eZ 4)

    ViewController mit TTL
    PublishToolService/Model
    
    LegacyPagelayout mit Error Handling
            
            new \Twig_SimpleFunction( 'cjw_cache_set_ttl', array( $this, 'setCacheTtl' ) ),
            new \Twig_SimpleFunction( 'cjw_fetch_content', array( $this, 'fetchContent' ) ),
            new \Twig_SimpleFunction( 'cjw_breadcrumb', array( $this, 'getBreadcrumb' ) ),
            new \Twig_SimpleFunction( 'cjw_treemenu', array( $this, 'getTreemenu' ) ),
    
    Fetch Functions
    
            allenfalls cjw_fetch_content( 'list', hash(...) )  oder sogar cjw_fetch( 'content', 'list', hash(...) )
            allenfalls sogar generischer Wrapper um alle legacy fetch functions?
            
            cjw_fetch_content_list
            cjw_fetch_content_tree

            cjw_fetch_content_class
            cjw_fetch_content_related_objects
            cjw_fetch_content_reverse_related_objects
            
            cjw_fetch_user_current_user
            ...
            

CjwPublishTools (rein eZ 5)

    EventListener
            Exeception Handling
                
    
    ContentTreeTraversal
            cjw_load_parent_location( locationId | location ) => location
            cjw_load_children( locationId | location ) => location[]
            cjw_load_location( locationId | content ) => location
            cjw_load_content( contentId | location ) => content

            cjw_load_locations( locationId | location )

    Utility
            cjw_file_exists( fileName ) => bool
            cjw_redirect( location | locationId | url )
            cjw_content_download_file( fileName )
    
    Templating
            cjw_render_location( )
            
            cjw_set_template_var( name, value )
            cjw_get_template_var( name ) => mixed
            cjw_get_default_language_code() => string
            
    ContentTypes
            cjw_get_content_type_identifier( contentTypeId | content ) => string
            cjw_get_content_type_attributes( contentTypeId | content ) => array
            
    Config
            Twig Globals via Twig Extension und Yaml File
            
            
            
    

noch unklar----

TwigConfigFunctions

        return array(
            new Twig_SimpleFunction(
                'cjw_siteaccess_parameters',
                array( $this, 'siteAccessParameters' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'cjw_config_resolver_get_parameter',
                array( $this, 'configResolverGetParameter' ),
                array( 'is_safe' => array( 'html' ) )
            ),
            new Twig_SimpleFunction(
                'cjw_config_get_parameter',
                array( $this, 'configGetParameter' ),
                array( 'is_safe' => array( 'html' ) )
            )
        );
