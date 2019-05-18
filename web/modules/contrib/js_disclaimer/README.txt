CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

JS Disclaimer allows to have a disclaimer dialogue message displayed to the user
on click of a link leading to an external site. The disclaimer message is
configurable from drupal administration section.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/js_disclaimer

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/js_disclaimer

FEATURES
--------

 * By default displays a jsvascript confirm dialogue on click of external link.

 * The default message displayed can be changed at /admin/config/js-disclaimer.

 * To bypass the disclaimer dialogue add css class "not-external" to links.
      <a href="https://google.com" class="not-external">Google</a>

REQUIREMENTS
------------

 * core/jquery

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


CONFIGURATION
-------------

Configure the Disclaimer dialogue message at /admin/config/js-disclaimer.

MAINTAINERS
-----------

Current maintainers:
 * Abhra Banerjee (flydragon865) - https://www.drupal.org/u/flydragon865
