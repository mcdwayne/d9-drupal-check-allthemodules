CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * D8 Port Development
 * Maintainers


INTRODUCTION
------------

This Views Published or Roles module allows you to add a filter to a view to
display Published or by a Role. In a perfect world you can add the Bypass
content access control permission to a role but most of time you may not want to
give certain roles that permission.

In my scenario I have the View Unpublished Module installed and I want a certain
role to only be able to view unpublished nodes for one node type.


REQUIREMENTS
------------

The Views Published or Roles module requires the following modules:
  * Views (now part of core since Drupal 8)


INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------
1. Create your view as you would normally view.
2. Under Filter Criteria select Add
3. Navigate to (Or Search) for: Content: Published or has role
4. Select Role(s)
5. Apply
6. Sometimes your view may give your a Content: Published filter. You should
   remove that filter.


D8 PORT DEVELOPMENT
-------------------
 * Joseph Olstad - https://www.drupal.org/u/joseph.olstad


MAINTAINERS
-----------
Current maintainers:
 * Albert Jankowski (albertski) - https://www.drupal.org/u/albertski
