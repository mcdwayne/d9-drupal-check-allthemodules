TAXONOMY FACETS
--------------------

TAXO FACETED NAVIGATION module - Faceted search with clean url's.

* INTRO
* INSTALLATION
* CONFIGURATION
* HOW TO USE


==========================================================================
                                         INTRO
==========================================================================

Progressive content filtering, with clean url's, using taxonomies as facets.

Ideal for eCommerce carts, real estate / properties sites, classified adverts, or other sites with multiple categories,
where users need to filter content progressively by applying filters from one or more categories.

Category Landing Pages
----------------------

Taxonomy terms are used as Facets to help users filter content progressively. Similar to the way it is done on most of
today’s eCommerce sites, like Amazon. This module can also help with SEO, so as the user applies different filters the clean
URL's are preserved with each filter change. For example, if the user applies these three filters:
Computer Monitors, Samsung and LCD, then the URL will look something like:

http://sitename.com/listings/computer-monitors/samsung/lcd

When the user then changes the filters to Computer Monitors, HP and LCD, the URL will change to:

http://sitename.com/listings/computer-monitors/HP/lcd

Filters can be applied in various permutations, so producing a clean URL for each unique filter combination will allow
search engines to index a huge amount of landing pages. Please be careful as duplication of content can be panished by
search engines, so seek advice of SEO experts.


Node pages
----------

When a user arrives on the “node body" page, like the product page, the full URL path will be preserved.

http://sitename.com/listings/computer-monitors/HP/lcd/Monitor-123-AB

If a user finds the same node (i.e product) with different filter permutations this will be reflected in the URL:

http://sitename.com/listings/multimedia/displays/HP/Monitor-123-AB

Futhermore, if a user arrives on the product page via a direct URL, say directly from a Google search, the filters from
the URL will be applied automatically and menu items expanded and highlighted accordingly.

For example, if a user arrives on the site via this URL:

http://sitename.com/listings/computer-monitors/HP/lcd/Monitor-123-AB

The menu tree / facets will look something like

Computer hardware
--Monitors (highlighted)
--Peripherals
--Hard discs
--Workstations

Brand
--Sony
--HP (highlighted)
--Dell
--Apple

TYPE
--LCD (highlighted)
--Plasma

But if they arrive on the same product page via a different URL, say:

http://sitename.com/products/multimedia/displays/HP/Monitor-123-AB

The menu tree / facets will now look something like

Stores
--Multimedia (highlighted)
--Books
--House appliance

Product type
--displays (highlighted)
--gaming
--handheld

Brand
--Sony
--HP (highlighted)
--Dell
--Apple

This module produces "menus" on the fly, i.e. no need for rebuilding menus or indexing as the menu items are not Drupal
menu items but just items in the block. This is useful for sites where taxonomies may change frequently.

Filters / facets blocks are cached for better performance.


==========================================================================
                                INSTALLATION
==========================================================================

1) Place this module directory in your "modules" folder (this will usually be
   "/modules/").  

2) Enable the Taxonomy Faceted navigation module in Drupal at:
   Administration -> Extend  (admin/modules)
   The Drupal core taxonomy module is required.
   

==========================================================================
                            CONFIGURATION
==========================================================================

Create your taxonomies
------------------------
Create taxonomies that will be used as filters.
admin/structure/taxonomy

Add url patterns
------------------
admin/config/search/path/patterns
Select pattern type = Taxonomy term
Chose a vocabulary
set Path pattern = [term:name] (you use different tokens or combination of tokens but do not
 put "/" character inside your pattern or filtering will not work)
 
Generate aliases
---------------------
Once you have set patterns for all vocabularies buk generate path aliases for taxonomy terms
admin/config/search/path/update_bulk


Expose taxonomy as Faceted Filters / Navigation blocks
------------------------
Go to Blocks admin page:
admin/structure/block
Administration » Structure » Blocks layout
Click on "Place block" button next to the desired area, like "Sidebar first".
Select "Taxonomy Facets block 1"
Set title of the block, for example if you adding Location filter type "Location".
Select vocabulary, so if you adding location filters select "Location" Vocabulary, assuming you already 
created Location Vocabulary and added location taxonomy terms.
Click on "Pages" tab, select "Show for the listed pages" and type:
/listings/*
/listings
on the text box. 
Save block

Add more blocks for other filters, using "Taxonomy Facets block n" blocks, you can create up to 5 filter blocks.

Node body
------------
As a end user, once filter your nodes, you weill eventualy  want to read more abut a 
particular node, and will click on the n ode title and end up in the node body. At this point the
Taxo Faceted filters will disappear. If you want the filters to stay on the node body page you need 
to add following to the block "Show for the listed pages" field:
/node/*

so your  settings in this filed will look like:
/listings/*
/listings
/node/*

This will make Filters blocks appear on all node body pages, which is fine if you use all content 
types in the filtering process. If you use only some content types than you need to use Conext
module to position Filters block on the node body pages.

NOTE: To achieve above functionality this module uses JS to append a taxonmoy_facets argument
to the node url. It uses class "node__title" to find all node titles and urls on the page, so make sure
to not to change this class on the node listing pages.

First argument
--------------------
The path where you taxonmy filters can be used is listings, so go to myste.com/listings
 to begin filtering content.
 This is known limitation and in the future this should be configurable by site admin.

Configure Teasers
----------------
The listing page will show node teaser, configure what you want to see in the node type configuration
For example if you using Article content type go to:
admin/structure/types/manage/article/display/teaser 



Configure other options
--------------------------
Go to admin section OF TAXONOMY FACETS module: 

admin/config/taxonomy_facets/adminsettings

Configure as desired




==========================================================================
                              HOW TO USE
==========================================================================

If you do not have any taxonomie terms in your system create some. 

For example create a vocabulary Catalog, 
create some terms in this vocabulary, for example:

Catalog
   Monitors
     LCD
     TFT
   Laptops
   Workstations
 
 Create another 2 vocabularies
 
 Brand
   Sony
   HP
   Acer
   
 Price range
  less than $100
  $100 - $200
  $200 - $500
  $500 - $1000
  $1000 +
  
Make sure url aliases are generated for above. This module will not work without URL aliases
   
Add some nodes in to your CMS and tag them with appropriate taxonomies: Lets 
say you have Product content type. Add 3 fields to the product content type. 
Field type is Entity  Reference. 
Add fields Catalog, Brand and Price range, of type Entity (Term) Reference, and select 
appropriate Vocabularies respectively.

Create some Products and associate with appropriate terms. For Example add 
Product node:  "Sony LCD monitor" that costs $400 and associate it with:
 Product-LCD
 Brand-Sony
 Price range - ($200 - $500)
 
Go to listings page and apply above filters, the node should appear in the listing 
 
