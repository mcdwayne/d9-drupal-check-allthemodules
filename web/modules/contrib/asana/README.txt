CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Asana module allows the integration between the https://asana.com/ site and 
Drupal.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/asana

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/asana


REQUIREMENTS
------------

This module requires the Official PHP client library for the Asana API
https://github.com/Asana/php-asana . A composer.json file is provided with the
module for the library installation.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.

 * Add the module composer.json file to your drupal composer.json file in the
   extra section, your file should look like this:

    "extra": {
        "merge-plugin": {
            "include": [
                "core/composer.json",
                "modules/custom/asana/composer.json"
            ],
        },
    }

 * Run 'composer update' and composer will install the Asana library.


CONFIGURATION
-------------

 * Configure the module settings in Administration » Configuration »
   Web services » Asana » Settings:

   - In the Personal Access Token field enter your Asana Personal Access Token.
     To generate the token in Asana go to: My profile settings... » Apps »
     Manage Developer Apps » Personal Access Tokens »
     Create New Personal Access Token. For add the Personal Access Token in 
     in the site you need the 'Administer Asana' permission.


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * David Csonka (davidcsonka) - https://www.drupal.org/u/davidcsonka
