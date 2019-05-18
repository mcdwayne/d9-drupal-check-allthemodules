CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Links

INTRODUCTION
------------

This module allows you to render all your menus with jsTree library via blocks

 * Once you have the module enabled you will have a new block called "jsTree menu".
   On each instance of this block type you will be able to select any of the existing
   menus. Then, every instance of this block will then render its menu with jsTree.

INSTALLATION
------------

 * You don't have to install any other module. In Drupal 8 version of this module
   "jquery_jstree" is no longer required.

 * Install the jstree libraries (https://www.jstree.com/) into DRUPAL_ROOT/libraries/jstree
   Once done, you should have this file DRUPAL_ROOT/libraries/jstree/jstree.min.js and all
   jstree themes under DRUPAL_ROOT/libraries/jstree/themes/*

 * [OPTIONAL] Install the "proton" theme for jsTree into libraries folder. Go to
   https://github.com/orangehill/jstree-bootstrap-theme and download it.

   Copy the dist/themes/proton folder to DRUPAL_ROOT/libraries/jstree/themes

   If done right, you will have style.min.css file on:
   DRUPAL_ROOT/libraries/jstree/themes/proton/style.min.css

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.

 * To render the tree icons correctly you will need to install the Bootstrap theme
      (default icons) or the Font Awesome module:

   - https://www.drupal.org/project/bootstrap
   - https://www.drupal.org/project/bootstrap_library
   - https://www.drupal.org/project/fontawesome

CONFIGURATION
-------------

 * Configure module's options in Administration » Configuration » User Interface
   » jsTree menu:

    - jsTree theme

      Allows you to choose between "default" jsTree theme or "proton" (which has to be
      downloaded)

    - Menu maximum height

      Allows you to choose the height of the menu.

    - Remove border of jsTree

      Allows to remove the border of the tree.

    - Normal icon

      Icon displayed on every node of tree except leaves. You can use Bootstrap glyphicons
      and/or Font Awesome icons.

      - Bootstrap glyphicons example: glyphicon glyphicon-tag (see more at http://getbootstrap.com/components/)
      - Font Awesome example: fa fa-file (see more at http://fontawesome.io/icons/)

    - Leaves icon

      Icon displayed on tree leaves. You can use Bootstrap glyphicons and/or Font Awesome
      icons.

      - Bootstrap glyphicons example: glyphicon glyphicon-tag (see more at http://getbootstrap.com/components/)
      - Font Awesome example: fa fa-file (see more at http://fontawesome.io/icons/)
LINKS
-----

https://www.jstree.com/
https://github.com/orangehill/jstree-bootstrap-theme
https://www.orangehilldev.com/jstree-bootstrap-theme/demo/
