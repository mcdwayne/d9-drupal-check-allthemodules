INTRODUCTION
------------

The Image Optimize TinyPNG module provides integration with the TinyPNG service
for the Image Optimize pipeline system.

  * For a full description of the module, visit the project page:
    https://drupal.org/project/imageapi_optimize_tinypng

  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/imageapi_optimize_tinypng


REQUIREMENTS
------------

This module requires the following modules:

  * Image Optimize (https://drupal.org/project/imageapi_optimize)

  * This module also requires the official TinyPNG PHP library: tinify.
    It is available from https://github.com/tinify/tinify-php however it is
    recommended to install it via composer so that it is autoloaded like other
    Drupal libraries.

    Exactly how you install this library via composer depends on your project
    setup, however some guidance is available on Drupal.org:

    https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies

    If you have installed this module via composer, then the tinify library will
    have been installed already as part of the composer process.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit:
    https://drupal.org/documentation/install/modules-themes/modules-7
    for further information.


CONFIGURATION
-------------

  * Configure Image Optimize pipelines in Administration » Configuration » Media
    » Image Optimize pipelines:

    * Either add a new pipeline or edit an existing one.

    * Add a new TinyPNG processor.

    * Obtain an API key from https://tinypng.com and enter it into the
      configuration form.
