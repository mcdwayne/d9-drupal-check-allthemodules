-= BaguetteBox Drupal module =-

Summary
=========================
This module provides integration with BaguetteBox library.

Requirements
=========================
 * BaguetteBox should be installed to libraries/baguettebox directory.

Installation
=========================
 * Install as usual, see http://drupal.org/node/895232 for further information.
 * Download BaguetteBox library from https://github.com/feimosi/baguetteBox.js.
 * Unzip the library and place files from dist directory to
   libraries/baguettebox directory.

Notes
=========================
 * You can use `drush baguettebox-download` command for easy installation of the
   library.
 * To make it work with Views you should either set "Use field template"
   checkbox or manually add "baguettebox" class in View field style settings.
