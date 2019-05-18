CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
The Migrate Cron module provides the functionality of executing the migrations on cronjob at a particular interval.


REQUIREMENTS
------------
This module depends on
    * migrate
    * migrate_plus


INSTALLATION
------------
* Install as you would usually install a contributed Drupal 8 module. See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-mod...

* Using composer and drush

```composer require drupal/migrate_cron
drush en migrate_cron```


CONFIGURATION
-----------
After installation, set individual cron intervals by accessing /admin/config/system/migrate-cron
er installation, set individual cron intervals by accessing /admin/config/system/migrate-cron


MAINTAINERS
-----------

 * Vadim Malasevschi (vadimski)
     - https://www.drupal.org/u/vadimski