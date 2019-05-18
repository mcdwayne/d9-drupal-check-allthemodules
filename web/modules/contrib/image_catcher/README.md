#IMAGE CATCHER

##SUMMARY
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How to use it
 * Maintainers


##INTRODUCTION
 **Image catcher** is a lightweight service allowing you to create images files
  from external URL or base64 source.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/image_catcher

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/image_catcher

##REQUIREMENTS
 * PHP 7
 * pathauto module

##INSTALLATION
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.


##CONFIGURATION
 * There's no configuration needed at all.


##HOW TO USE IT
 This module provides two main methods through one service :
 * `image_catcher.manager`
 ** `createFromBase64($image_base64, $dir_name, $image_name)`
 ** `createFromUrl($image_url, $dir_name)`
 Read more about Services and dependency injection in Drupal 8 :
 https://www.drupal.org/docs/8/api/services-and-dependency-injection/services-and-dependency-injection-in-drupal-8


##MAINTENERS
 Current maintainers:
 * Maxime Roux (MacSim) - https://www.drupal.org/u/macsim
