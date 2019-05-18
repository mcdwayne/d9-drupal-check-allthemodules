CONTENTS OF THIS FILE
=====================
* INTRODUCTION
* REQUIREMENTS
* INSTALLATION
* CONFIGURATION

INTRODUCTION
============
This module allows image files to be optimised using the Kraken.io web service
at http://kraken.io. After the initial configuration of a Kraken.io account, an
administrator of your site can then configure image optimize pipelines with the
Kraken optimize processor.

REQUIREMENTS
============
 * Image Optimize module https://drupal.org/project/imageapi_optimize

INSTALLATION
============
 * Install via composer

CONFIGURATION
=============

1. Create a new pipeline at /admin/config/media/imageapi-optimize-pipelines/add.

2. Choose 'Kraken optimize' in the 'Select new processor' list and add it as a
   processor.

3. Enter the API details from your Kraken.io account.

4. Select lossy compression for smaller filesizes, if desired.

5. Either change a single image style to use your new pipeline or change the
   sitewide default to use it at /admin/config/media/image-styles

Read more about 'Working with images in Drupal 7 and 8, here:

https://drupal.org/documentation/modules/image

