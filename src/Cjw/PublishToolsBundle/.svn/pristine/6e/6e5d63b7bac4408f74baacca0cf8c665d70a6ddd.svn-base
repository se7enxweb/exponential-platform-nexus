ezPublish 5 appetizer
=====================

## 1. What's the point of the appetizer

The purpose of the appetizer is to provide an API that
allows you to retrieve content in a way that's 
more user friendly to handle than the current solution.

## 2. What functionality does the appetizer provide.

The appetizer is split in three modules.

* The search module
* The node module
* The index module

## 3. How do I get started using the bundle?

Add your routes to your curent routing.yml configuration.

    cjwpublishtools_appetizer_node:
        path: /appetizer/ezpnode/{node_id}
        defaults: { _controller: CjwPublishToolsBundle:Appetizer:node }
    
    cjwpublishtools_appetizer_search:
        path: /appetizer/ezpsearch/{search_term}
        defaults: { _controller: CjwPublishToolsBundle:Appetizer:search }
    
    cjwpublishtools_appetizer_index:
        path: /appetizer/ezpindex/{dummy}
        defaults: { _controller: CjwPublishToolsBundle:Appetizer:index }


Before getting started, add the minimal configuration to your parameters.yml:

    parameters:
        cjwsiteaccess.<your_siteaccess_name>.parameters:
            tree_root_location_id: <your_tree_root_location_id>
            appetizer:
                cjw_folder: # add at least one content type identifier

#### The node module

To call a certain node by its id

The node modules provides an easier way to call a content object by its node id.
To call the a node by its route call

    /appetizer/ezpnode/<node_id_of_class>

Which will return a response similar to this

    {
        "data": {
            "ezpclass": "cjw_folder",
            "ezpparentid": 539,
            "ezpparentname": "Kur- und Heilwald Heringsdorf ",
            "is_node_root_id": false
        }
    }

    
#####How to override the content type identifier
     
**.../parameters.yml** 

    parameters:
        cjwsiteaccess.<your_siteaccess_name>.parameters:
            tree_root_location_id: <your_tree_root_location_id>
            appetizer:
                cjw_folder: # <- replace this content type identifier
                    AliasName: ezpfolder # <- with 'ezpfolder' 

**the expected response**

    {
        "data": {
            "ezpclass": "ezpfolder", // <- replaced
            "ezpparentid": 539,
            "ezpparentname": "Kur- und Heilwald Heringsdorf ",
            "is_node_root_id": false
        }
    }
    
##### How to show the current node's children.

To show the current node's subitems, add the content type identifiers
to the 'Children'

#### search api

    /ez/ezpsearch.<search_term>.json

The search module provides an easier way to trigger a content search
using the ezPublish API system.

Sample response for legacy_simple:

    [
        {
            "ezpname": "Wiebendorf",
            "ezpnodeid": "1111"
        },
        {
            "ezpname": "Verlinkungen",
            "ezpnodeid": "2222"
        },
        {
            "ezpname": "Ihre Werbung ",
            "ezpnodeid": "3333"
        }
    ]
    
How to configure search:
    
    appetizer:
        advanced:
            Search:
                search_type: 'legacy_simple' # 'legacy_simple', 'ez5_simple', 'ez5_advanced'

What are the search_types above?  
-   legacy_simple:  
    uses the legacy search and only returns the object names and node ids.

-   ez5_simple:  
    uses the ezpublish 5 search and only returns the object names and node ids.

-   ez5 advanced:   
    uses the ezpublish 5 search and returns structured results with child elements and corresponding datamap.

#### form api

        /ez/ezpnode.<form_node_id>.json  

The form api allows the user to display form data, and submit form values.

-   how to display form data:

        appetizer:
            # --> insert field type identifier
            cjw_order_form_books:
                # --> replacing field identifier
                AliasName: orderformbooks
                # --> necessary to make the node api return structure as form.  
                IsForm: true 
                # --> email configuration block.
                FormSettings: 
                      mailer: 
                          subject: 'Bestellung ist eingegangen'
                          sender:  'info@gutshaeuser.de'
                          receiver:
                                  - 'gabriel@jac-systeme.de'
                                  - '$E-Mail Adresse' #--> 'E-Mail Adresse' is the label of the form data request.
                #--> attribute list (same as the node module).
                AttrFilterList:
                      image: ezpimage
                      short_description: ezpintro

-  how to submit form data:  
   post data must be submitted to the following url   
        
        /ez/ezpform.<form_node_id>.json
        
#### tag api

        /ez/ezptag.<tag_id>.json

The tag api retrieves content objects by their tag id.

configuration.  
    
        appetizer:
            advanced:
                SimpleTags: true # returns only names and node ids, false formats output.

            cjw_folder:
                AliasName: ezpfolder
                Tags:
                    visible: true # show in tags.

sample response.

        "data": {
            "id": 33,
            "parentTagId": 220,
            "mainTagId": 0,
            "name": "Wochenkalender 2009"
        },
        "children": [
            {
                "ezpname": "Groß Timkenberg",
                "ezpnodeid": 2997
            },
            {
                "ezpname": "Buggenhagen",
                "ezpnodeid": 1175
            }
        ]
        
#### modified tree api

The modified tree api allows the user to retrieve a list of all content items with its corresponding modified date.


#### index api

The index module allows the user to fetch all the content with a single request.

        parameters:
            cjwsiteaccess.mv-waelder_heringsdorf_mobile.parameters:
                tree_root_location_id: %cjwsiteaccess_heringsdorf_tree_root_location_id%
                homepage_location_id: %cjwsiteaccess_heringsdorf_tree_root_location_id%
                appetizer:
                    advanced:
                        EnableCache: false
                    cjw_folder:
                        AliasName: ezpfolder
                        Index:
                            visible: true # visible
                        AttrFilterList:
                              title: ezpname
                              short_description: ezpintro
                              image: ezpimage
                              bottom_title: bottom_title                        Children:
                              alias: children
                              child_fetch_limit: 10
                              depth: 1
                              allow_content_types:
                                    - cjw_image
                                    - cjw_article
        
                        Imagevariations:
                              image:
                                - medium
                                - large
        
                    cjw_folder_site:
                        AliasName: ezpfoldersite
                        Index:
                            visible: true # visible
                        AttrFilterList:
                              title: ezpname
        #                      short_description: ezpintro
        #                      image: ezpimage
        #                      modus: mymodus
        #                      tags: my_tags
                        Children:
                              alias: children
                              child_fetch_limit: 10
                              depth: 1
                              allow_content_types:
                                    - cjw_image
                                    - cjw_folder
                              index_content_types:
                                    - cjw_image
                                    - cjw_article
                                    - cjw_folder
        
                    cjw_article:
                        AliasName: ezparticle
                        Index:
                            visible: true # visible
                        AttrFilterList:
                              title: ezpname
                              short_description: ezpintro
                              description: ezpbody
                              bottom_title: ezpbottomtitle
                              image: ezpimage
                              geo_information: ezpgeoinformation
                        Imagevariations:
                              image:
                                - large
                        Children:
                              alias: children
                              child_fetch_limit: 10
                              depth: 1
                              allow_content_types:
                                    - cjw_image
                                    - geo_information
                    cjw_image:
                        AliasName: ezpimage
                        Index:
                            visible: true # visible
                        AttrFilterList:
                              title: ezpname
                              description: ezpintro
                              image: ezpimage
        
                        Imagevariations:
                              image:
                                - medium
                                - large
        
                    geo_information:
                        AliasName: ezpgeoinformation
                        AttrFilterList:
                              title: ezpname
                              geodata: geodata
