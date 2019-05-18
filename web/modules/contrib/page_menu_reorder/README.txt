CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Page menu reorder module helps to rearrange menu links of a page.
A new reorder tab isadded onto a page if the page has menu links.
It works similar to Drupal 7 version of submenu reorder module
(https://www.drupal.org/project/submenu_reorder) but this module
is developed in Drupal 8.

Normally we reorder the menu links on the
menu administration page admin/structure/menu/manage/main.
But it’s not so easier for a large site with hundreds of menu links.
Also we need to restrict access to the global menu admin page.
This module helps to overcome the issues.

This module works with the menu `Main navigation` for now.
This could be easily extended to work with other menus.

Thanks to the University of Surrey for supporting us in developing the module.


 * For a full description of the module visit:
   https://www.drupal.org/project/page_menu_reorder

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/page_menu_reorder


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Page menu reorder module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Configure the settings under: /admin/config/pagemenureorder
    to enable the content types needed
    3. Clear cache
    4. Enable permissions to the roles under Page menu reorder under:
    Administration -> People -> Permissions
    5. Reorder menu tab could be seen on a page if it has menu links


MAINTAINERS
-----------

 * Chandra Rajakumar (chandraraj) - https://www.drupal.org/u/chandraraj
 * Carolina Longhini (calonghini) - https://www.drupal.org/u/calonghini
 * Sarah Barnett (Sarah.Barnett) - https://www.drupal.org/u/sarahbarnett-0
 * Daniel Gittings (dangittings) - https://www.drupal.org/u/dangittings

Supporting organization:

 * University of Surrey - https://www.surrey.ac.uk/
