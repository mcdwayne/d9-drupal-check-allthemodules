CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Square Pixels Scale adds an image effect (for use in image styles) that allows 
scaling based on the total number of pixels in an image (as calculated by 
multiplying the width by the height). The original aspect ratio of the image is 
maintained.

This module is useful in cases where images of varying aspect ratios (such as
logos) will be displayed together and should have apparent sizes more similar
than the basic scale effect can provide.


REQUIREMENTS
------------

This module has no special requirements.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.


CONFIGURATION
-------------

The following configuration options are available once a square pixels scale
image effect has been added to an image style:

* Total square pixels: This target number will be approximated by multiplying
the width and height of the scaled image. The actual result will most likely 
not be the exact number, but slightly below or above.

* Maximum width: A maximum width to which the image should be scaled
(optional).

* Maximum height: A maximum height to which the image should be scaled
(optional).

* Allow upscaling: Let square pixels scale make an image larger than its
original size.


MAINTAINER
----------

 * Rick Hawkins (rlhawk) - https://www.drupal.org/u/rlhawk
