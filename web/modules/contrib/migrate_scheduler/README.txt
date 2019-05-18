CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation and configuration
 * Recommended modules
 * Support
 * Maintainers


INTRODUCTION
------------

The migrate_scheduler module provides the functionality of executing the
migrations on a particular schedule.

Cron API which is built into the Drupal core is used to schedule the migrations.


INSTALLATION AND CONFIGURATION
------------------------------

Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/node/895232 for further information.

The module currently relies on the configuration set in the settings.php file,
with a plan to support the configuration from the admin UI.

Place the following configuration variable in any of the active settings.php, or
settings.local.php, or settings.local.php

```
$config['migrate_scheduler']['migrations'] = [
  'migration_1' => [
    'time' => 3600,  # To be executed after every 1 hour.
    'update' => TRUE  # To be executed with the --update flag.
  ],
  'migration_2' => [
    'time' => 28800, # To be executed after every 8 hours.
  ]
];
```

The above configuration is similar to executing:

* `drush mim migration_1 --update` - after every hour.
# `drush mim migration_2` - after every 8 hours.



RECOMMENDED MODULES
-------------------

 * Migrate Plus (https://www.drupal.org/project/migrate_plus):
   Enhancements to core migration support

 * Migrate Tools (https://www.drupal.org/project/migrate_tools):
   Tools to assist in developing and running migrations.


SUPPORT
-----------
To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/migrate_scheduler

MAINTAINERS
-----------

Current maintainers:
 * Ajit Shinde - https://www.drupal.org/u/ajits

This project has been sponsored by:
 * CoLab Coop
   CoLab is a worker-owned digital agency that started in 2010 in Ithaca,
   New York. Visit: https://colab.coop for more information.
