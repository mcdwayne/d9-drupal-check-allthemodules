Stress Test Add Translations
============================

INTRODUCTION
------------
Every time you create, edit delete a translation, Drupal will create a new revision for every existing translation (even if they didn't change). This module provides a stress test to check how well Drupal handle websites with 500 languages to a single node and generates statistics about the performance for every translation added.

REQUIREMENTS
------------
- Git & composer installed locally
- Drupal 8 dev dependencies, run `composer install --dev`
- Drupal core's module - Simpletest
- You also need this module to be installed in order to be able to run the tests

INSTALLATION
------------
This module can be installed via usual Drupal modules installation ways, e.g. either via Drush/Drupal console or from the admin UI.


SAMPLE
-------------
If you have the site installed locally on URL `localhost` you can run this command.
In `--dburl` you should replace **mysqlusername** with mysql username, **mysqlpassword** with mysql password, and **databasename** with database name, which are usually specified in your own settings.php

`sudo -u www-data php ./core/scripts/run-tests.sh --clean && sudo -u www-data php ./core/scripts/run-tests.sh --dburl 'mysql://mysqlusername:mysqlpassword@localhost/databasename' --url http://localhost --suppress-deprecations --verbose --directory ./modules/custom/stress_test_add_translations/tests`

Note: user which is used to run the test can be differs on some operation systems,
 for Ubuntu it is www-data by default.

Result statistics will be placed in `/sites/simpletest/stress_test_results.csv`

MODIFICATIONS
-------------
Inside the file `tests/src/Functional/StressAddTranslationsTest.php` you can change variable `LANGUAGES_COUNT` to the amount of languages you want to test with and `FIELDS_COUNT` to the amount of translatable field you want to have on the node you test with.
