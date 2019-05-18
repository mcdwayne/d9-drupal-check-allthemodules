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

The Collapsiblock module provides the functionality to make blocks collapsible.

This module is intended for site-builders who are new to Drupal with relatively
simple needs. We will try to accommodate feature requests and options but will
balance those with the need for a simple UI.

 * For a full description of the module visit:
   https://www.drupal.org/project/collapsiblock

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/collapsiblock


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Collapsiblock module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Block Layout and place a block.
    3. Now when placing blocks there is an additional fieldset on the
       configuration page. Choose the "Block collapse behavior". Save block.
    4. If you want to set global settings for collapsiblock, navigate to
       Administration > Configuration > User Interface > Collapsiblock.

The global settings allow you to:
    1. Choose a default action for all blocks.
    2. Choose whether to save the last state of blocks in a cookie for each user.
    3. Choose whether active links are kept open (useful for menu blocks).


TROUBLESHOOTING
---------------

If your blocks are not behaving as expected, ensure that the template
for your block(s) uses the {{ title_prefix }} and {{ title_suffix }}
elements to wrap the portion of the block that should be used as the
non-collapsing, clickable portion of the block. The JS script will try to
collapse anything outside that wrapper that is in the block except for
contextual links which have their own behaviours.

MAINTAINERS
-----------

 * Max Pogonowski (Darvanen) - https://www.drupal.org/u/darvanen
 * Sonvir Choudhary (sonvir249) - https://www.drupal.org/u/sonvir249

Supporting organization:

 * QED42 - https://www.drupal.org/qed42
