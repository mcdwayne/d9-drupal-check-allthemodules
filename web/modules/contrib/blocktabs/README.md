INTRODUCTION
------------

Block tabs, this module provide basic tabs function for Drupal.

Different from quicktabs module:

 * quicktabs do not have a workable version in Drupal 8, this module provide
   Drupal8 version only.
 * Deep integration with Drupal8's API, Plugin, Block, Entity and Config API.
 * Provide simple tabs functions, you can control the CSS by your self.
 * base on jquery.ui.tabs.
 * Support import/export blocktabs using Config API.
 * Tab type using Plugin system, it is very easy to extend.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/blocktabs

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/blocktabs

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------
 
 * When enabled, in order to configure follow these steps:
   - 1) add a blocktabs at admin/structure/blocktabs, 
        for example "Blocktabs test"
   - 2) add views tab to "Blocktabs test", add custom block content tab to 
        "Blocktabs test", it support "views tab", "block content tab",
		"block plugin tab"
   - 3) navigate to admin/structure/block/list/bartik, click the "Place block"
        button, you could see "Blocktabs: Blocktabs Test", you can use it now.
   - 4) you could use it on panels config page.

MAINTAINERS
-----------

Current maintainers:
 * howard ge (g089h515r806) - https://www.drupal.org/u/g089h515r806
