CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Basic Usage
 * Recommended modules
 * Maintainers


INTRODUCTION
------------

This module provides functionality to autotag content using Google's Natural
Language API.

 * To access the Google NL API documentation, visit
   https://googlecloudplatform.github.io/google-cloud-php/#/docs/google-cloud/v0.28.0/language/languageclient.

REQUIREMENTS
------------

 * You must have a valid Google service account JSON key file with Natural
   Language enabled for the project.
 * This module depends on the Google NL API module and the Straw module, which
   are installed automatically when installing with composer.


INSTALLATION
------------

Install this module via composer by running the following command:
* composer require drupal/google_nl_autotag


CONFIGURATION
------------
Configure Google NL API in Administration » Configuration » Google NL » Google
NL Autotag Settings or by going directly to
/admin/config/google_nl/google_nl_autotag_settings.

From this page, you can select which content types you want to have the module
autotag, as well as which fields on the content type you want to use to
determine the tags. Only fields containing textual content are supported.


BASIC USAGE
------------
Once it has been configured for a content type, this module adds a new, disabled
field to the content type which stores the tags which Google has determined are
associated with the content (the full list of potential tags can be found in
Google's documentation at
https://cloud.google.com/natural-language/docs/categories ). This field cannot
be edited directly. Instead, whenever a node is saved, it will get populated
automatically. The tags themselves are stored in a hierarchical taxonomy tree,
under the Google NL Autotag Categories vocabulary.

At times, you may wish to update the tags on all content (such as after first
installing the module or after changing its configuration). To do so, navigate
to Administration » Configuration » Google NL » Google NL Autotag Batch Update,
or go directly to /admin/config/google_nl/google_nl_autotag_batch_update, and
click "Start" to autotag all applicable nodes. This is a simple resave of the
node, which triggers the autotagging described above.


RECOMMENDED MODULES
------------

* Google NL API (https://drupal.org/project/google_nl_api)


MAINTAINERS
------------

Current maintainers:
 * Clint Randall (camprandall) - https://drupal.org/u/camprandall
 * Jay Kerschner (JKerschner) - https://drupal.org/u/jkerschner
 * Brian Seek (brian.seek) - https://drupal.org/u/brianseek
 * Mike Goulding (mikeegoulding) - https://drupal.org/user/mikeegoulding

This project has been sponsored by:
 * Ashday Interactive Systems
   Building your digital ecosystem can be daunting. Elaborate websites,
   complex cloud applications, mountains of data, endless virtual wires
   of integrations.
