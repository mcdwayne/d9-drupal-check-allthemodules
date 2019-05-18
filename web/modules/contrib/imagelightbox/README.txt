ImageLightBox Drupal module

Summary
=========================
This module provides integration with ImageLightBox library.


Installation
=========================
 * Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8
 * Define image style "imagelightbox" and "imagelightbox small"
 * Go to "Content Type" -> add "Image Field" -> "Manage Display" -> Select the "ImageLightBox" format and configure the image style.

Notes
=========================
 * You can use `drush imagelightbox-download` command for easy installation of the
   library.
 * To make it work with Views you should either set "Use field template"
   checkbox or manually add "imagelightbox" class in View field style settings.
 * Download ImageLightBox library from https://github.com/osvaldasvalutis/imageLightbox.js.
 * Unzip the library and place files from dist directory to
   libraries/imagelightbox directory.