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

The Menu Manipulator menu provides site builders with automatic
filtering of menu item. Developers also have a new helpful service
to filter menu trees in Drupal 8.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/menu_manipulator

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/menu_manipulator


REQUIREMENTS
------------

This module requires the following modules:

 * Menu Link Content (in core: `menu_link_content`)

RECOMMENDED MODULES
-------------------

 * Menu UI (in core: `menu_ui`)
 * Markdown filter (https://www.drupal.org/project/markdown):
   When enabled, display of the project's README.md help will be rendered
   with markdown.


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

This module automatically filter menus. You must enable the custom functions
under Admin > Configuration > User Interface > Menu manipulator settings.


TROUBLESHOOTING
---------------

 * If menus are not filtered, check configuration in the admin setting page.

FAQ
---

Q: How does the magic happen?

A: Easy. There's a new service `menu_manipulator.menu_tree_manipulators`
  that is used to apply filters upon Menu Trees before rendering.

  We have created a custom method to automatically filter by language.
  
  You can find code example in `menu_manipulator.module` file.


MAINTAINERS
-----------

Current maintainers:
 * Matthieu Scarset (matthieuscarset) - https://www.drupal.org/user/3471281/

This project has not been sponsored - yet.
