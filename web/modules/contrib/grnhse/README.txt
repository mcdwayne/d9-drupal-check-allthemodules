Greenhouse Job API
================================================================================

Greenhouse provides services for recruiting and job application management 
including an API for accessing job board contents. This module provides a 
Drupal wrapper and entity type that uses the Greenhouse Service Tools SDK 
to import and sync basic Job data from the Greenhouse API. 

This module imports entities that can be published on a Drupal site 
using Views or other Drupal structures.


Installation
--------------------------------------------------------------------------------

Do the usual download and place in the `modules` directory process, or if you
are using composer: `$ composer require 'drupal/grnhse'`.

When the code is ready, use Drush or the UI to install the module as usual.


Configuration
--------------------------------------------------------------------------------

Configure the API settings using configuration details provided by Greenhouse at:

  [YOUR SITE]/admin/config/services/grnhse

Greenhouse Job entities will be synced with each cron run or as configured above.


Maintainers
--------------------------------------------------------------------------------

Current maintainers:

* Tom Kiehne (tkiehne) - https://www.drupal.org/u/tkiehne


Issues
--------------------------------------------------------------------------------

If you have issues with this module you are welcome to submit issues at
https://www.drupal.org/project/grnhse (all submitted issues are public).
