CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details & Usage
 * Configuration
 * Incompatibility
 * FAQ


INTRODUCTION
------------
This module deletes all database tables and files for the current site 
and redirect back to install.php so that one can reinstall Drupal from 
scratch. The purpose of this module is to assist in the testing of 
install profiles. It is only useful during development.


MODULE DETAILS & USAGE
----------------------
Drupal Reset Module provides you option to delete all your files or your
site database or the complete files and database.

Once the module is enabled, access Drupal Reset from the main 
Configuration page under Development.


CONFIGURATION
-------------

There are no such configurations for this module.

* Drupal Reset Form is available at 
  /admin/config/development/drupal_reset
  where you have 3 options to go with for resetting your Drupal site:
  - Reset All
  - Reset Files
  - Reset Databases

INCOMPATIBILITY
---------------
Drupal Reset is not fully compatible with the Domain Access module. 
If you use Domain Access, before running Drupal Reset on your site, you 
must comment out the configuration line in the settings.php file which 
looks like this: include 
DRUPAL_ROOT . '/sites/all/modules/contrib/domain/settings.inc';

FAQ
---

Q: Can Drupal Reset module delete module/theme files? 

A: No, as of now Drupal Reset module provides you option to delete just 
   files/database.



Q: For 'Reset Files' option, do Drupal remove drupal file entities?

A: No

