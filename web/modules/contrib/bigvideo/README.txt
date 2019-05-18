CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Warning
 * Requirements
 * Installation
 * Configuration

INTRODUCTION
------------
The BigVideo module provide the ability for attach background video to site pages.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/bigvideo

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/bigvideo

WARNING
-------
You'll probably need to adapt/update your theme styles to make your theme look good with background videos.

REQUIREMENTS
------------
This module requires the following libraries:
  - Video.js (https://github.com/videojs/video.js)
  - BigVideo.js (https://github.com/dfcb/BigVideo.js)
  - ImagesLoaded (https://github.com/desandro/imagesloaded)

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.
 * Download the Video.js(not newer than 5.x.x), BigVideo.js and ImagesLoaded libraries.
 * Place the libraries in the appropriate directories:
    libraries/video-js/video.min.js
    libraries/video-js/video-js.min.css
    libraries/imagesloaded/imagesloaded.pkgd.min.js
    libraries/bigvideojs/css/bigvideo.css
    libraries/bigvideojs/lib/bigvideo.js

CONFIGURATION
-------------
 * Add new BigVideo source at admin/config/user-interface/bigvideo/sources
 * Add BigVideo pages at admin/config/user-interface/bigvideo/pages
 * (extra) BigVideo provide reaction (BigVideo Background) for Context module.
