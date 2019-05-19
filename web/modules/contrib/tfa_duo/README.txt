CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Two Factor Authentication for Duo Security module provides a plugin to the
tfa framework for Duo Security.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/tfa_duo

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/tfa_duo


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Key - https://www.drupal.org/project/key
 * TFA - https://www.drupal.org/project/tfa
 * Encrypt - https://www.drupal.org/project/encrypt


INSTALLATION
------------

 * Install the Two Factor Authentication for Duo Security module as you would
   normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------
Things you will need:
- An encryption profile using a valid encryption key.
- A key of type Duo with your credentials in json format:
  {"duo_application":"SOMETHING","duo_secret":"SOMETHING","duo_integration":"SOMETHING","duo_apihostname":"SOMETHING"}
* "duo_application" - A key YOU generate and keep secret from Duo. It should be at least 40 characters long.
* "duo_integration" - The "Integration key" in the Duo web site
* "duo_apihostname" - The "API hostname" in the Duo web site
* "duo_secret" -The "Secret key" in the Duo web site

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > People > Two-factor
       Authentication to enable this plugin.


MAINTAINERS
-----------

 * Manuel Garcia - https://www.drupal.org/u/manuel-garcia
 * Michael Hess (mlhess) - https://www.drupal.org/u/mlhess
