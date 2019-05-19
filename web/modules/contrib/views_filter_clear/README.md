CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Views Filter Clear module allows 'Clear' links to be configured for individual exposed views filters.
These links clear any submitted value from the view. They operate independently of one another. This is
different from the _Reset_ buttons available for exposed forms, which reset the entire form to its default
state.

 * For a full description of the module visit:
   https://www.drupal.org/project/views_filter_clear

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/views_filter_clear

NOTES
-----

* No styling of the links is attempted in this module.
* The links are simply appended to each exposed filter's label/title.


REQUIREMENTS
------------

This module requires the core Views module, and optionally the Views UI module.


INSTALLATION
------------

 * Install the Views Filter Clear module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

For each exposed filter that should add a _clear_ link, configure the exposed filter and check the _Add clear link_ checkbox.

MAINTAINERS
-----------

 * Jonathan Hedstrom (jhedstrom) - https://www.drupal.org/u/jhedstrom

For a full list of contributors visit:

 * https://www.drupal.org/node/980666/committers

Module development sponsored by:

 * Workday, Inc. - https://www.drupal.org/workday-inc
