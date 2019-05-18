CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The PhotoShelter is meant to integrate photo's stored on PhotoShelter onto your
site. It does not copy your photo files to your server, but allows you to access
and display them from your Drupal site. Each photo, gallery, and collection on
your PhotoShelter site is saved as a type of symlink with meta data as a PS
Photo, PS Container. If you don't check the "Allow synchronization
of private files" option on configuragtion page, images marked visible by everyone
in your PhotoShelter library are the only ones that will be copied over.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/photoshelter

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/photoshelter


REQUIREMENTS
------------

This module requires the following outside of Drupal core.

 * Remote stream wrapper - https://www.drupal.org/project/remote_stream_wrapper
 * Queue UI - https://www.drupal.org/project/queue_ui
 * PHP 7.0


INSTALLATION
------------

 * Install the PhotoShelter module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Navigate to Administration > Configuration > Media > PhotoShelter
       API to set the PhotoShelter account credentials and API key.
    3. Save configurations.


MAINTAINERS
-----------

 * olivier.br - https://www.drupal.org/u/olivierbr
 * Blake Morgan (blakemorgan) - https://www.drupal.org/u/blakemorgan
 * John Ouellet (labboy0276) - https://www.drupal.org/u/labboy0276

Supporting organizations:

 * Inovae - https://www.inovae.ch/
 * Brigham Young University - https://www.drupal.org/brigham-young-university
 * Tandem - https://www.drupal.org/tandem
