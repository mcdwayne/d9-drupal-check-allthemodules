CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------

Simple MegaMenu module provide a easy way to build mega menu.

This module provides a new content entity type, Simple MegaMenu. These entities
can be managed as the node entity type : Create bundle, add view mode, add
fields, customize display, etc. For each bundle created, you have to specify
on which menu this bundle will be available.

You can then reference, from each Menu link content item, any entities
available for this bundle.

The simple MegaMenu entity attached to an item is then rendered from the Twig
template provided by the module. To achieve this, two new Twig functions are
available :

* view_megamenu(Url $url, 'view_mode') : this function allows to render the
  simple MegaMenu entity attached to an item url, in any view mode needed.
* has_megamenu(Url $url) : this function allows you to check if an item has a
  Simple MegaMenu entity attached to it.

You can then easily customize how your megamenu looks like by overriding the
Twig template in your theme.

By default, the module provide two view modes for the simple mega menu
entity type : Before and After.

The default template use theses two view modes.

You can then easily update your custom template and use any custom view mode
you want, to feet your needs. And you can too render entities attached where
you want.

Simple MegaMenu entities are translatable and revisionable and play nice with
a mutlilingual site.

REQUIREMENTS
------------

None

RECOMMENDED MODULES
-------------------


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

CONFIGURATION
-------------

* Create a simple mega menu type, as any Content type
* For each simple mega menu type; you have to set on which menu it will be used.
* Configure the simple mega menu type as for a content type :
  - add fields
  - configure form mode
  - configure view mode
* Create som simple mega menu entities
* Edit any Menu link content item form the menu set on the simple mega menu type
  created above. Select the simple mega menu entity you want attach to the menu
* Override and customize the menu--simple-megamenu.html.twig template belong
  your needs


TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
