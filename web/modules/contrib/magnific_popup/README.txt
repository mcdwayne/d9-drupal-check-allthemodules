CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module provides field formatters for the Magnific Popup jquery plugin by
Dmitry Semenov (https://github.com/dimsemenov/Magnific-Popup).
This plugin is ideal for creating pop-up galleries of pictures or videos.

The 8.x module is still under heavy development with new features being added
as they are thought of or suggested.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/magnific_popup

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/magnific_popup


FEATURES:
---------

Has different gallery types including:

 * 'First Item Only' - Display only one thumbnail from multiple images on the
   field, but all images can be viewed by navigating in the pop-up gallery.
 * 'Gallery - All Items' - Show all thumbnails and allow all items to be seen
   in the pop-up gallery.
 * 'Separate Items' - Show all thumbnails, but don't allow navigation to other
   items in the pop-up gallery. This only displays the clicked image in the
   pop-up.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

Integration with Video Embed Field to provide pop-up videos for different
providers with various embed and auto-play options. Also has the gallery options
listed above available for the video thumbnails and galleries.

 * Video Embed Field - https://www.drupal.org/project/video_embed_field


INSTALLATION
------------

 * Install the third-party Magnific Popup library
   https://github.com/dimsemenov/Magnific-Popupas "magnific-popup" under
   DRUPAL_ROOT/libraries.
   To be correctly detected and used, the JS and CSS should be located at these
   paths:
   libraries/magnific-popup/dist/jquery.magnific-popup.min.js
   libraries/magnific-popup/dist/magnific-popup.css
   Paths not using the "dist" folder (for versions before 8.x-1.3) are still
   supported. Although it is advisable to update the paths to use the "dist"
   older.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content types > [Content type
       to edit] > Manage display.
    3. Choose "Magnific Popup" as the formatter and optionally configure
       Gallery Style options on an image or video embed field field.
    3. Save.

MAINTAINERS
-----------

 * Jay Dansand (jay.dansand) - https://www.drupal.org/u/jaydansand
 * Eric Goodwin (Eric115) - https://www.drupal.org/u/eric115
 * Drew Nackers (nackersa) - https://www.drupal.org/u/nackersa

License - MIT
Github - https://github.com/dimsemenov/Magnific-Popup
