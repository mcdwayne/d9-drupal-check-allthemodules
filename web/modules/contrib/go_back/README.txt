CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Go Back module add a button that allows us to return to the previous
page that we visited on the site or customize the page where we want it
to redirect.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/go_back

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/go_back

REQUIREMENTS
------------

This module no require additional modules.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

We must configure the block to work correctly
in /admin/structure/block/manage/gobackblock.
There is no configuration. You need to place a block in a region.
We have 2 modes of use:
* Custom URL: It allows us to add a custom url to the block button
              so that the user can go where we want, the customization is
              independent for each type of content. This url will be used also
              if the user comes from outside the site and the smart mode
              is activated.
* Mode Smart: The button of the block will take as url
              the last one of the site that we have visited, we can
              activate this option for each type of content, in the
              case that we come from outside the site, the url
              custom will be url.

MAINTAINERS
-----------

Current maintainers:
 * Antonio Sanchez (saesa) - https://www.drupal.org/user/3561094

This project has been sponsored by:
 * SDOS
 We create, develop and implement technology that feels.
