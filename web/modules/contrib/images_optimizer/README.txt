CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Similar module
 * Maintainers

INTRODUCTION
------------

The Images Optimizer module provides a simple way to optimize uploaded images.

Every uploaded image is automatically optimized if its mime type is supported
by one of the registered optimizers. The original uploaded image is destroyed
in the process.

Additionally, if you use images styles, created derivative images will also be
optimized.

We provide two basic optimizers that handle JPEG and PNG images, but you can
easily create your own if they do not fit your needs.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/images_optimizer

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/images_optimizer


REQUIREMENTS
------------

The module requires the following modules:

 * File (Core module)

If you are using the two provided optimizers, the module requires these
additional libraries:

 * pngquant (https://pngquant.org/)
 * jpegoptim (https://github.com/tjko/jpegoptim)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.

 * If you are using the two provided optimizers:
   * Install pngquant. See https://pngquant.org/#download
   * Install jpegoptim. See https://github.com/tjko/jpegoptim


CONFIGURATION
-------------

Configure the module in "Configuration" > "Media" > "Images Optimizer".

You can select the optimizers you want to use and configure their options.

If you need a more advanced configuration, you should create your own
optimizer by registering a service that implements the `OptimizerInterface`
interface, and by tagging it with the "images_optimizer.optimizer" tag.


SIMILAR MODULE
---------------

 * Image Optimize (or ImageAPI Optimize)
  https://www.drupal.org/project/imageapi_optimize


MAINTAINERS
-----------

Current maintainers:
 * Thomas Calvet (fancyweb) - https://drupal.org/user/3575426
 * Benjamin Rambaud (beram) - https://drupal.org/user/3508624
