CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Colorized gmap module allows to add a google map on the site as a drupal block and customize it.
This module expands the standard UI and allows to create a block. At the colorized gmap block creation page you are able to customize a standard google map (e.g. to colorize water, landscape , etc.) You will see changes on the map after every action.

Features:
 * colorize any elements of the map
 * hide unnecessary map controls
 * change map controls position
 * customize marker image and caption
 * add multiple blocks
 * exportable via 'Features' module


 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/colorized_gmap


 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/colorized_gmap


INSTALLATION
------------

 * Download colorpicker (By Stefan Petre) library
   http://www.eyecon.ro/colorpicker and put its content to
   "colorpicker" directory inside libraries directory
   (usually /libraries) so you should have
   sites/all/libraries/colorpicker directory and js/colorpicker.js and
   css/colorpicker.css in it.
   See https://www.drupal.org/node/1440066 for more details.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * Enter Google Maps API key from 'admin/config/content/colorized_gmap_apikey'
   Visit https://developers.google.com/maps/documentation/javascript/get-api-key
   and for further information to get get API key.
   You need to choose Google Maps Javascript API there.


CONFIGURATION
-------------
 * Visit 'admin/config/content/colorized_gmap' to add API Key.


MAINTAINERS
-----------

Current maintainers:
 * Artyom Zenkovets (azenkovets) - https://www.drupal.org/u/azenkovets
 * Usov Denis (usdv) - https://www.drupal.org/u/usdv

This project is created by ADCI Solutions team (http://drupal.org/node/1542952).
