CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Drupal 8 MegaMenu module helps the user create custom menu, video and Drupal
blocks.

 * For a full description of the module visit:
   https://www.drupal.org/project/we_megamenu
   or https://www.weebpal.com/guides/megamenu-d8-documentation

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/we_megamenu


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Drupal 8 MegaMenu module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Block layout.
    3. In the Main Menu region select "Place block". The block library will
       open. Scroll down to find the Drupal 8 Mega Menu black and "Place bock."
    4. Configure the block with the content types, pages, and roles that can
       access the block. Select the region in which to display from the "Region"
       dropdown. Save block.

Drupal 8 Mega Menu main UI consists of 2 parts:
 * Drupal 8 Mega Menu simulator: simulate the Mega Menu frontend interface that
   has been simplified (without style). There are 3 types of clickable elements
   in the Simulator. Depending on the element the user selects, the Toolbox will
   display different contents:
    * menu-item
    * submenu
    * column

 * Toolbox Area: The area that allows the user to configure selected elements
   in the menu simulator. General Toolbox is displayed by default:
    * Style: Set style for the Mega Menu.
    * Animation: Set animation effect for the Mega Menu.
    * Action: Activate the submenu display.
    * Auto arrow: Show or hide an arrow next to the items that have sub-menus.
    * Show submenus: Show or collapse submenus when browsing on small screens.
    * Save button: Save the configurations.
    * Reset button: Go back to the last save.
    * Reset to default button: Back to the original when the user set up Main
      menu.

Drupal 8 Mega Menu UI configuration:

    1. Navigate to Administration > Structure > Drupal 8 MegaMenu.
    2. There is list of menus, each menu here is an instance of the existing
       Drupal menus.


MAINTAINERS
-----------

 * Hai Nguyen Ngoc (HaiNN) - https://www.drupal.org/u/hainn

Supporting organization:
 * WeebPal - https://www.drupal.org/weebpal
 * Website - https://www.weebpal.com
 * Backend developer - buivankim2020@gmail.com
 * Frontend developer - khoangk.weebpal@gmail.com
