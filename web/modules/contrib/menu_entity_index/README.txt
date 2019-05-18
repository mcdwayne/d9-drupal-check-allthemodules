CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Menu Entity Index module builds and maintains an index of Menu Link Content
entities and their referenced entities. It also provides some basic Views
integration for Menu Link Content entities.

Features provided by the module:

  * A database table that contains menu items and entities referenced by these
    menu items. Tracked information for each menu item includes menu name, menu
    level, entity type, bundle, entity id, uuid, langcode, parent entity type,
    parent id, parent uuid, target entity type, target bundle, target entity id
    and target langcode.

  * Basic Views integration for Menu Link Content entities.

  * A form field widget for entity forms that can be used to show a listing of
    all menu items that reference the entity on entity edit forms.

For a full description of the module, visit the project page:
https://www.drupal.org/menu_entity_index

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/node/add/project-issue/menu_entity_index


REQUIREMENTS
------------

This module depends on the menu_link_content module provided by Drupal Core. If
you want to use the views integration, you also need the views module provided
by Drupal Core.


RECOMMENDED MODULES
-------------------

 * Views (provided by Drupal Core), if you want to use the views integration.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Go to the configuration page of the module at Configuration > Search and
   metadata > Menu Entity Index.

 * Configure the menus and entity types that should be included in Menu Entity
   Index. For example, to track all menu items in the main menu, that reference
   content or taxonomy term entities, add 'Main navigation' as a tracked menu
   and 'Content' and 'Taxonomy term' as tracked entity types and click on
   'Save configuration'.

 * A batch process that scans the selected menus for referenced entities will be
   initiated. Once this batch process has finished, the module will keep track
   of additions, updates or deletions of menu items itself and the module is
   ready to use.

 * If you want to show a listing of all menu items of an entity on its edit
   form, go to the 'Manage form display page' of its bundle/entity type. If
   tracking is enabled for that entity type, you'll see a new 'Menu links' field
   in the 'Disabled' region. Move it to the desired position to enable it and
   click on the 'Save' button.

 * Make sure to look over the permissions of the module. There is one permission
   to configure module settings and one permission to view the menu links form
   widget on entity edit forms.

MAINTAINERS
-----------

Current maintainers:
 * Patrick Fey (feyp) - https://drupal.org/u/feyp
 * David Franke (mirroar) - https://drupal.org/u/mirroar

This project has been partly sponsored by:
 * werk21 GmbH
   werk21 is a full service agency from Berlin, Germany, for politics,
   government, organizations and NGOs. Together with its customers,
   werk21 has realized over 60 Drupal web sites (since version 5).
   Visit http://www.werk21.de for more information.
