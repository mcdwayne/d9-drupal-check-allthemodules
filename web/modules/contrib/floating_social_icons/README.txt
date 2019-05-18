CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Floating Social Icons module allows sites to display social icons and links
to other social sites. This module does not use javascript, it uses twig if else
conditions to set classes based on condition. This module does not share,
instead it directs to the URL of the social site.

 * For a full description of the module visit:
   https://www.drupal.org/project/floating_social_icons

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/floating_social_icons


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

The Floating Social Icons module uses Font Awesome Method SVG with js instead of
webfonts.

 * Font Awesome Icons - https://www.drupal.org/project/fontawesome


INSTALLATION
------------

 * Install the Floating Social Icons module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the Floating Social Icons
       module and its dependencies.
    2. Navigate to Administration > Structure > Block Layout.
    3. Select the region to place the block. A default block was created when
       the module was enabled with the name Floating Social Block. Select "Place
       Block".
    4. Configure the block and enter the details in their respective fields.
    5. A minimum of two fields must be filled in.
    6. The icon will not be displayed if the field is empty.
    7. In the "Display Icons" field select where to display the block: left,
       top, bottom, or right.
    8. Uncheck the Display title field and place the block in the desired
       region.
    9. Select the appropriate visibility settings.
    10. Select "Save block".
    11. Navigate to font awesome settings page uncheck Use version 4 shim file?
    12. Click Save configuration.
   
This block is also responsive for mobile and tabs.


MAINTAINERS
-----------

 * Maheshwaran Jayagopal (Maheshwaran.j) - https://www.drupal.org/u/maheshwaranj

Supporting organization:

 * Drupal Partners - https://www.drupal.org/drupal-partners-0

Our Services include Drupal Development, Drupal eCommerce Development, Drupal
Migration ,Drupal Maintenance, Drupal Multisites, Drupal Intranet.
