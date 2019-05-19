CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Drupal by default does not allow redirect responses to be redirected to external
destinations.

This module allows that.

It introduces a configurable list of external hosts which are white-listed. So
anytime the redirect response is served the module checks if the destination
contains trusted host. If yes then the redirect is normally processed.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/trusted_redirect

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/trusted_redirect


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Trusted redirect module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Search and metadata >
       Trusted redirect for configurations.
    3. Add to the list of trusted hosts to be redirected within the destination
       query string. Enter one host per line.
    4. Save configuration.


MAINTAINERS
-----------

 * Wolfgang Ziegler (fago) - https://www.drupal.org/u/fago
 * Radoslav Terezka (hideaway) - https://www.drupal.org/u/hideaway

Supporting organization:

 * drunomics - https://www.drupal.org/drunomics
