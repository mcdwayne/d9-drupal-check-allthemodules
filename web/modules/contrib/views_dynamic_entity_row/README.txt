CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Views Dynamic Entity Row module provides dynamic row plugin that allows
to select individual view mode for each entity rendered by Views.

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/views_dynamic_entity_row


REQUIREMENTS
------------

This module requires Views module enabled.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Configure entity types and bundles that you want to use dynamic entity
   view mode during View render process:

   - Go to module's configuration page

     You can find this option in the Modules list page, Views settings page,
     or you can use direct path:
     /admin/structure/views/settings/dynamic-entity-row

   - Select entity types and bundles

     If selected entity type has bundles then two options appear: all bundles
     support or per-bundle support of dynamic view mode.

   - Set view mode for entities on their add/edit form

     By default dynamic view mode for particular entity can be set on their
     add/edit form that extends ContentEntityForm class. "Dynamic View Mode"
     extra field settings can be changed on Entity Form Display config page.

 * Set view mode for entities on their add/edit form. By default dynamic view
   mode for particular entity can be set on their  add/edit form that extends
   ContentEntityForm class. "Dynamic View Mode" extra field settings can be
   changed on Entity Form Display config page.

 * Create or edit View and select "<Entity Type> (dynamic)" row plugin in "Show"
   section. If entity has no dynamic view mode set that default one will be
   selected from Dynamic Entity Row plugin option.


MAINTAINERS
-----------

id.tarzanych (Serge Skripchuk) - https://www.drupal.org/u/id.tarzanych
