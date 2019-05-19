CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Structure of the CSV
 * Maintainers

INTRODUCTION
------------

This module allows uploading CSV files from where extract the data and create new user account with the information contained in the file. In addition, it allows selecting which fields will be filled with the information extracted.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/user_csv_import

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/user_csv_import
   
REQUIREMENTS
------------

This module does not have any special requirement, since it works with Users of Core.

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
   
CONFIGURATION
-------------

Once you have installed the module, a new button will appear on the user administration page with the name "Import users from CSV".

By clicking on the button you will be redirected to a form where you can upload the CSV file and configure the following options:

 * The role or set of roles that will be applied to the new users that are created.

 * The fields of the User entity that may be filled with the data extracted from the CSV file. The fields email and username will always be mandatory.
 
STRUCTURE OF YHE CSV
--------------------

For the import propoerly, the CSV file to be loaded must follow a specific structure.

In the first row, each column will contain the machine name of the field where you want to store the value. In the following rows but following the same pattern of columns, the values will be stored.

MAINTAINERS
-----------

Current maintainers:

 * Marc Fernandez -  Welldone - https://www.drupal.org/u/mcfdez87
 