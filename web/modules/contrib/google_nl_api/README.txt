CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Recommended modules
 * Maintainers


INTRODUCTION
------------

 This module provides functionality to access Google's Natural
 Language API.

 * To access the Google NL API documentation, visit
   https://googlecloudplatform.github.io/google-cloud-php/#/docs/google-cloud/v0.28.0/language/languageclient.

REQUIREMENTS
------------

 * You must have a valid Google service account json key file with Natural
   Language enabled for the project.
 * The <a href="https://drupal.org/project/encryption">encryption</a> module
 is required, and will be automatically installed when using composer.


INSTALLATION
------------

Install this module via composer by running the following command:
* composer require drupal/google_nl_api


CONFIGURATION
------------
 Configure Google NL API in Administration » Configuration » Google NL » Google
 NL API Settings or by going directly to
 /admin/config/google_nl/google_nl_api_settings:

 * Google key file contents
 - This is the Google NL API key file (.json). See
   https://cloud.google.com/natural-language/docs/reference/libraries#client-libraries-install-php
   to create a new project ID.


RECOMMENDED MODULES
------------

* Google NL Autotag (https://drupal.org/project/google_nl_autotag)


MAINTAINERS
------------

Current maintainers:
 * Clint Randall (camprandall) - https://drupal.org/u/camprandall
 * Jay Kerschner (JKerschner) - https://drupal.org/u/jkerschner
 * Brian Seek (brian.seek) - https://drupal.org/u/brianseek
 * Mike Goulding (mikeegoulding) - https://drupal.org/user/mikeegoulding

This project has been sponsored by:
 * Ashday Interactive
   Building your digital ecosystem can be daunting. Elaborate websites,
   complex cloud applications, mountains of data, endless virtual wires
   of integrations.
