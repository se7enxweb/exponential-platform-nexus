


New Blockdefinition/ViewType flexline 
===================

src/AppBundle/Resources/config/layouts/blocks.yml
   
    block_definitions:
        list:
            view_types:
                list:
                    item_view_types: &list_item_view_types
                # cjw custom item used for cjw_content_embedded objects 
                flexline:
                    name: 'Flex Line'


Blockaufbau
===========


Landingpage Home:
----------------

content
- content_header
  - title
  - intro


1. Grid: Standard with intro | manuel pick | css class :
   cjw-gallery-grid

2. List: Flexline | Manuell pick 


Landingpage (ng angepasst):
-----------

content
- content_header
  - title
  - intro
- body (leer)

Änderungen:

* `ezimage`-Attribut *image* hinzugefügt

Kinder:
1. Grid: Standard with intro | article + shortcut (6), sort by parent | 
   css class : cjw-gallery-grid

2. List: Flexline | Content Embedded + Gallery | sort by parent defined

Artikel: (ng angepasst)
--------

content
- content_header
  - title
  - intro
- image
- body


1. List: Flexline | Content Embedded + Gallery | sort by parent defined


Category (ng angepasst)
--------
neue Blockstruktur:

content
- content_header
  - title
  - intro
- body
