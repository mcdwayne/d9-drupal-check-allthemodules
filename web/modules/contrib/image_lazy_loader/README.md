# INTRODUCTION

The Image Lazy Loader module allows administrators/developers to pick if an
image should be loaded normally or lazy-loaded in the 'Manage display' when
images appear on window scroll, and speed up the website avoiding massive
images rendering before they are viewed.

# REQUIREMENTS

This module requires the Lozad.js plugin, available from Github. Download the
plugin from https://github.comApoorvSaxena/lozad.js, and place into the
libraries directory so that the lozad.min.js file can be found here:
/libraries/lozad/dist/lozad.min.js

The module requires the following module:
* Responsive Image


# INSTALLATION

* Install the module as you would normally install a Drupal contributed module.

* Before you can use the module, you will need to download the Lozad.js library
  as documented in the Requirements section above.


# CONFIGURATION

This module has no configurable options. However to use the module:
* edit the image field you want under under 'Manage Display'
* click the 'settings' icon
* (un)check the 'Lazy load this image' option
