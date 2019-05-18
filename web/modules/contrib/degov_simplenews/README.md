CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module extends the contrib module Simplenews with some 
(mostly GDPR-relevant) features.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/degov_simplenews

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/degov_simplenews


REQUIREMENTS
------------

This module requires following module outside of Drupal core:

 * Simplenews - https://www.drupal.org/project/simplenews


INSTALLATION
------------

 * Install the deGov Simplenews module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > deGov Settings > 
    Simplenews settings and set a privacy policy page for each enabled
    language. You can also change the amount of time unconfirmed subscribers
    will be kept in the database before they're removed via cron.
    3. In your Simplenews subscription form you should now see additional 
    fields for the first and last name, as well as a checkbox for users to 
    consent to their personal data being processed.

MAINTAINERS
-----------

 * Marc-Oliver Teschke (marcoliver) - https://www.drupal.org/u/marcoliver
 * Sascha Hannes (SaschaHannes) - https://www.drupal.org/u/saschahannes
 * Peter Majmesku - https://www.drupal.org/u/peter-majmesku

Supporting organizations:

 * publicplan GmbH - https://www.drupal.org/publicplan-gmbh
