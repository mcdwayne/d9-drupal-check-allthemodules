CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This module allows the users to browse particular pages in a specific language.
The classical use case is to allow displaying the frontend of the site in one
language and still keep most of the backend in English (or another language of
your choice), but it can have other usages.


REQUIREMENTS
-------------

Drupal 8.x
Interface Translation, Language, File, Field module enabled.


INSTALLATION
------------

Install the this module as you would normally install a
contributed Drupal module.

If you are not familiar with drupal, please visit:
https://www.drupal.org/node/1897420 for detail steps of Installing Modules.
If you've already been a frequently user, install modules from the command
lines is the fastest way to extend your installation.

 * Install with composer: composer require drupal/<modulename>
   Visit https://www.drupal.org/docs/develop/using-composer/
   using-composer-to-manage-drupal-site-dependencies for further information.

 * Install with drush: drush dl module_name
   Visit https://www.drupal.org/docs/8/extending-drupal-8/
   installing-modules-from-the-command-line for further information.

Enable this module from extend list page Home > Administration.


CONFIGURATION
-------------

The module provides an additional language negotiation mechanism that can be
enabled on Home > Administration > Configuration > Regional and language >
Languages.

* Navigate to Administration >  Configuration > Regional and language >
   Languages,Enable 'Administration language'.

* Navigate to Administration > Configuration > Regional and language >
   Languages > Detection and selection, define the language & path of
   application.

* You will also need to change the detection order. It needs to come before other language detections.

MAINTAINERS
-----------

* Pol Dellaiera - https://www.drupal.org/u/pol
* Delphine Lepers - https://www.drupal.org/u/delphine-lepers
