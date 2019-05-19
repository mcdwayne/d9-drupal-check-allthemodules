Swiper Slider
=============

A module to create sliders using swiper slider js library

Installation
-------------
This module needs to be installed via Composer, which will download the
required libraries.

1. Add the Drupal Packagist repository

   ```composer config repositories.drupal composer https://packages.drupal.org/8
    ```
This allows Composer to find Swiper Slider and the other Drupal modules.

2. Download Swiper Slider

   ```composer require "drupal/swiper_slider"
   ```
This will download the latest release of Swiper Slider.


3. Install dependencies

To download the third party libraries we user Bower.
Bower is a command line utility. Install it with npm.

``` npm install -g bower ```

Bower requires node, npm and git.

Than go into the module root and run

``` bower install ```

Now you will see a bower_components folder.
