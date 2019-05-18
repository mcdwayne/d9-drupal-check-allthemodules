CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
 
INTRODUCTION
------------

The client hints module implements the ideas presented in 
https://httpwg.org/http-extensions/client-hints.html and/or 
https://developers.google.com/web/updates/2015/09/automating-resource-selection-with-client-hints 
in a decidedly less sophisticated way, while working in any reasonable browser 
that supports Javascript as well as with non-HTML consumers.

Following the ideas in the aforementioned articles, the contextual size & 
resolution of images as displayed in the browser is calculated on the client 
(this is implemented via Javascript by default) and images are served in an 
appropriate - because only slightly larger than displayed - image style.

Consult above links on why and how this might fit your need or taste rather 
than responsive image styles.

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/client_hints
   
   
REQUIREMENTS
------------

This module requires the following modules:

 * Image (https://www.drupal.org/docs/8/core/modules/image)

Furthermore, the following Javascript library must be installed:

 * imagesLoaded (https://imagesloaded.desandro.com)
 
 
INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information. If you're not already using Composer 
   (https://getcomposer.org/), you might want to check that out because it's 
   neat.
 * Download the imagesLoaded library from https://imagesloaded.desandro.com and 
   place it in a /libraries/imagesloaded/ directory so the full path to the
   file is /libraries/imagesloaded/imagesloaded.pkgd.js and make sure to 
   rebuild the cache.
   
   
CONFIGURATION
-------------

The module has no menu or modifiable settings. There is no configuration. The 
module will, however, probably not work well (or at all) in combination with 
Responsive image styles 
(https://www.drupal.org/docs/8/mobile-guide/responsive-images-in-drupal-8) so 
proceed at your own peril.


MAINTAINERS
-----------

Current maintainers:
 * Karsten Violka (k4v) - https://www.drupal.org/u/k4v
 * Jan Stöckler (jan.stoeckler) - https://www.drupal.org/u/janstoeckler
 * Tobias Zimmermann (tstoeckler) - https://www.drupal.org/u/tstoeckler
