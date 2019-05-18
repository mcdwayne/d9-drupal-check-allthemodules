
CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
-----------
 * DB LOG FILTER module is used to restrict log messages by type and level.
 * Useful to restrict unwanted message types and reduce watchdog size for
    better performance.
 * Majorly useful in production sites when we want to log only
    limited messages.
 * Logging can be restricted either by the Levels. To Log only Error Level logs
   for a site, you can check "Errors" under "Select Severity Levels." in the
   configuration page.
 * Custom logging can also be done ignoring all the core logs too, by setting
   the values under "Enter DB Log type and Severity Levels."

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
 * Click on Configure link in modules page.
 * Or Go to http://YOURSITE/admin/reports/dblog-filter , configure type and
    level.
 * To restrict by Severity Level, Select from the allowed severity levels.
 * To restrict by Custom Logging, set it up under "Enter DB Log type
   and Severity Levels."
   Give one per line as (TYPE|LEVEL)
    - php|notice,error,alert
    - mymodule|notice,warning
   This only allows to log watchdogs of type "php" and "mymodule", with their
   respective severity levels.
   For example, Following are the Logs Recorded(as per the above settings):
   1. \Drupal::logger('mymodule')->notice('@build:',
        array('@build' => print_r($build, true)));
   2. \Drupal::logger('php')->alert('@build:',
        array('@build' => print_r($build, true)));
   Logs not recorded:
   1. \Drupal::logger('php')->warning('@build:',
       array('@build' => print_r($build, true)));
