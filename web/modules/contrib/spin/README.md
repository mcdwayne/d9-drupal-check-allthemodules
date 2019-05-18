Module: 3D Image Spinner
Author: bkelly bill@williamakelly.com

CONTENTS OF THIS FILE
====================
 * Description
 * Requirements
 * Installation
 * Maintainers


Description
===========
This module will allow a sitebuilder to easily add a 3D image spinner and
slideshow to a site.

The module uses the Sirv 3D image spinner and the MagicToolBox MagicSlideshow.
Sirv provides an easy to use interface to create image spinners. The
agicSlideshow has both a free, (branded) and a paid slideshow.

  * For a full description of the module, visit the project page:
    https://drupal.org/project/spin

  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/spin


Requirements
============
This module has the following dependencies:

  * Required modules
    * Field (https://drupal.org/project/field)
    * File (https://drupal.org/project/file)
    * Image (https://drupal.org/project/image)
    * Libraries (https://drupal.org/project/libraries)
    * Text (https://drupal.org/project/text)

  * Required libraries
    * https://scripts.sirv.com/sirv.js
    * https://www.magictoolbox.com/static/magicslideshow-trial.zip


Installation
============
  1. Copy the 'spin' directory in to your Drupal modules directory.

  2. Download the magicslideshow package and copy the magicslideshow.js into the
     libraries directory so the path is:
     .../libraries/magicslideshow/magicslideshow.js.

  3. Download the sirv.js file and place it into the libraries directory so the
     path is: .../libraries/sirv/sirv.js.

  4. Enable the module.

  5. Go to /admin/config/spin and configure the slideshow defaults.

  6. The Spin field can now be added to any content type.


CONFIGURATION
-------------
The slideshow and thumbnails can be configured.

  * The slideshow configuration is sitewide.
  * Field instances have individual thumbnail configuration settings.


MAINTAINERS
===========
Current maintainers:
  * Bill Kelly (bkelly) - https://drupal.org/user/265918
