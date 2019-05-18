CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

This module allows performant A/B and multivariate testing on your site via a
lightweight JS script. It is similar to Optimizely and Adobe Test&Target, but
focused more on flexibility and power and less on allowing non-technical folks
to setup tests.

 * The project page is here:
   https://www.drupal.org/project/abjs

 * For a full description of the module and instructions on how to use, visit
   the documentation page:
   https://www.drupal.org/node/2716391

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/2601142


REQUIREMENTS
------------
This module does not have any dependencies.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

   - Administer A/B Test Configuration

     This permission allows one to create, edit, and delete Tests, but not
     to create, edit, or delete Conditions or Experiences, which require
     JavaScript.

   - Administer A/B Test Scripts and Settings

     This permission allows one to create, edit, and delete Experiences and
     and Conditions, as well as change the general settings of the module.

 * Customize the module settings at admin/config/user-interface/abjs/settings.
   If Ace Code Editor is chosen, it will load via CDN on the Condition and
   Experience Edit pages.


TROUBLESHOOTING
---------------

 * If a test is not working:

   - In your browser, check for the presence of cookies prefixed by abjs_
     or your custom prefix set in the Settings tab.


MAINTAINERS
-----------

Current maintainers:
 * Matt Mowers - https://www.drupal.org/user/1353878
 * Thalles Ferreira - https://www.drupal.org/u/thalles
