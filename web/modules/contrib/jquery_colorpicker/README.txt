CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Maintainers

INTRODUCTION
------------

The Drupal 8 branch of Jquery Colorpicker offers jQuery Colorpicker widget for
the (Hexidecimal) Color field, and a Form API form element than can be used as
follows:

<?php
$form['element'] = [
  '#type' => 'jquery_colorpicker',
  '#title' => t('Color'),
  '#default_value' => 'FFFFFF',
];
?>


REQUIREMENTS
------------

This module depends upon:

- The jQuery Colorpicker library (https://www.eyecon.ro/colorpicker/)
- The Drupal (Hexidecimal) Color module
  (https://www.drupal.org/project/hexidecimal_color)
- The Drupal Vendor Stream Wrapper module
   (https://www.drupal.org/project/vendor_stream_wrapper).

These dependencies are managed through Composer when the module is installed
using:

composer require drupal/jquery_colorpicker


INSTALLATION
------------

Add the module to your project using:

composer require drupal/jquery_colorpicker

Then enable the module as you would any Drupal module.


MAINTAINERS
-----------

This module is maintained by:

* Jaypan (https://www.jaypan.com)
* Plopesc (https://www.drupal.org/u/plopesc)
