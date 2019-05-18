CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Module
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Prepared Data module is a batch processing framework for preparing expensive
data.

 * For a full description of the module visit:
   https://www.drupal.org/project/prepared_data

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/prepared_data

This README document is currently not a sufficient documentation about
what this module is providing, how to configure it properly and
how to use it. It's still some sort of proof of concept and
heavily in progress. A more detailed documentation and example is
being planned.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

RECOMMENDED MODULE
------------------

The Mustache Templates module can use Prepared Data
as Json data source for client-side DOM content synchronization.

 * Mustache Templates - https://www.drupal.org/project/mustache_templates

INSTALLATION
------------

This section is not complete yet and will be updated soon with further
info.

 * Install the Prepared Data  module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.
*  The module provides a service endpoint delivering Json-encoded data
   at /prepared/get with the query arguments "k" for the key which
   identifies the prepared data. The data can be further filtered
   with subset keys as query arguments "sk". A combination of
   key and arbitrary subset keys can be accessed via so-called
   shorthands, as query argument "s".

CONFIGURATION
-------------

This section is not complete yet and will be updated soon with further
info.

Configure the module at /admin/config/prepared-data.
There you can enable available processor plugins.
This module does not provide useful processors out of the box,
except for a simple date and time generator.

Currently available processors are included in
- The Shariff Backend module,
  https://github.com/BurdaMagazinOrg/module-shariff-backend

MAINTAINERS
-----------

 * Maximilian Haupt (mxh) - https://www.drupal.org/u/mxh

Supporting organization:

 * Hubert Burda Media - https://www.drupal.org/hubert-burda-media
