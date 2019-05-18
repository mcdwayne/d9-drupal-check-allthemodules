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

This module allows the ability to restrict access to configured menus and route paths based 
on specified roles.

Menu Custom Access provides additional restrictions to the Admin Toolbar menu.  
Drupal 8's out of the box permissions for menus and menu items prevent the ability
to restrict menu operations to specific menus.  Sometimes we do not want users to have 
access to adding new menus or edit/update exisiting menus.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/menu_custom_access

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/menu_custom_access


REQUIREMENTS
------------

This module requires the following modules:

 * Admin Toolbar (https://www.drupal.org/project/admin_toolbar)
 * Devel (https://www.drupal.org/project/devel)



INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules 
   for further information.


CONFIGURATION
-------------

This module requires

	* Custom Roles (Will not work with default anonymous and authenticated users)
	* Configure routes, menu and roles:

		/admin/config/search/menu_custom_access


MAINTAINERS
-----------

Current maintainers:
 * Steven Luongo (vetchneons) - https://www.drupal.org/u/vetchneons

This project has been sponsored by:
 * Herkimer Media
   With an office in Madison, WI, Herkimer, LLC provides Web Development, 
   creation of marketing collateral materials, and photography services to 
   companies and organizations worldwide. We're a tight-knit team of Web developers, 
   creatives, and business consultants dedicated to the goals of increasing our clients' 
   sales, enhancing their productivity, and delivering Web experiences that are innovative, 
   compelling, and effective.

   Visit: https://herkimer.media/ for more information.
