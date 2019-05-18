CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

*IMPORTANT: Do not use this module on a production environment.*

This module will mask all the current data in your database related to the
users. By default it will try to mask the default fields created by Drupal,
but this can easily be extended providing a mapping for other fields.

This can be useful if the data in the database is sensitive and should not be
used in a different place from the production server.

 * For a full description of the module visit:
   https://www.drupal.org/project/mask_user_data

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/mask_user_data


REQUIREMENTS
------------

This module requires the following modules.

* Faker library - https://github.com/fzaninotto/Faker/releases
* PHP 5.4+ is required.


INSTALLATION
------------

Install the Mask User Data module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.



CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Mask User Data and enable
       the checkbox to make sure the masking map is visible. Once this field
       is visible provide a map in JSON format that should match field names
       (or properties) and Faker functions (ie: _mail_ maps to _email_,
       _field_phone_ maps to _phoneNumber_, etc.).
       These settings could be overridden in _settings.php_ providing
       _$config['mask_user_data_map_array'] = array(...)_.
    3. In drush, you can now run *drush mud* or you can trigger cron too.
    4. In drush, you can also provide an argument to mask the data of only
       one user. Example: *drush mud 1*. This will mask the data of user uid=1.
    5. In People listing (/admin/people), there is a mass action available. 
       Select the desired users and mask their data.



AUTOMATIC DEPLOYMENTS (FOR DEV ENVIRONMENTS)
--------------------------------------------

An automatic deployment to a dev/local environment could look like this (this
assumes that you have a _live_ alias set up):

    * drush -y sql-drop
    * drush -y sql-sync @live default
    * drush -y en mask_user_data
    * drush -y mud



MAINTAINERS
-----------

 * Fran Garcia-Linares (fjgarlin) - https://www.drupal.org/u/fjgarlin

Supporting organization:

 * Amazee Labs - https://www.drupal.org/amazee-labs
