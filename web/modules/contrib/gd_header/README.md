CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The GD Header module provides a block with the page title and a configurable
image in the background.

 * For a full description of the module visit:
   https://www.drupal.org/project/gd_header

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/gd_header


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the GD Header module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Media > Image styles and add
       some image styles.
    3. Navigate to Administration > Structure > Block layout > Place block in
       the appropriate region and select the GD Header block.
    4. Navigate to Administration > Structure > Content types > [Content type to
       edit] and add at least one image field.
    5. Select which image field to associate with the GD Header from the drop
       down for the desired content type. Select the image style. Save Block.

Features:
 * For each node type, the user can choose a field (of type image) to use for
   the background image.
 * Global images can be added in the block configuration, so nodes without
   images can still display nice headers with backgrounds, as well as non-node
   pages (Users, Views, Contact, ...).
 * The user can choose the image style to use.

CSS:
 * For better effect, it's meant to be used on the whole width of the screen.
 * By default, the background image is centered, no-repeat, cover and fixed. On
   (some) IOS devices, the background image scrolls because they can't correctly
   handle the fixed position.
 * By default the title is centered and has a shadow.
 * The user can always override the CSS.


MAINTAINERS
-----------

 * Guillaume Duveau - https://www.drupal.org/user/173213
