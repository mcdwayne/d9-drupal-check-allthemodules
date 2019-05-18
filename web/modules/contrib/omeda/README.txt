CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Recommended modules
 * Maintainers


INTRODUCTION
------------

 The Omeda module provides basic Drupal integration to the Omeda API. It will
 allow you to connect to the API, automatically store comprehensive brand config
 in state based on a cron run, and configure basic settings such as whether or
 not you're in testing mode.

 * To submit bug reports and feature suggestions, or to track changes:
 - https://www.drupal.org/project/issues/omeda

 * To access the Omeda API documentation:
 - https://jira.omeda.com/wiki/en/Wiki_Home


REQUIREMENTS
------------

 The <a href="https://drupal.org/project/encryption">encryption</a> module
 is required, and will be automatically installed when using composer.


INSTALLATION
------------

Install this module via composer by running the following command:
* composer require drupal/omeda


CONFIGURATION
------------
 Configure Omeda in Administration » Configuration » Omeda » Omeda Settings
 or by going directly to /admin/config/omeda/settings:

 * Production API URL
 - This is the API url used when not in test mode.

 * Testing API URL
 - This is the API url used when in test mode.

 * API Mode
 - This determines whether or not you are in testing mode.

 * App ID / API Key
 - This is passed to the API as x-omeda-appid.

 * Input ID
 - This is passed to the API as x-omeda-inputid for update calls.

 * Brand Abbreviation
 - This is passed as part of the URL for all API calls requiring it.

 * Client Abbreviation
 - This is passed as part of the URL for all API calls requiring it.


RECOMMENDED MODULES
------------

* Omeda Subscriptions (https://drupal.org/project/omeda_subscriptions)
* Omeda Customers (https://drupal.org/project/omeda_customers)


MAINTAINERS
------------

Current maintainers:
 * Clint Randall (camprandall) - https://drupal.org/u/camprandall
 * Jay Kerschner (JKerschner) - https://drupal.org/u/jkerschner
 * Brian Seek (brian.seek) - https://drupal.org/u/brianseek
 * Mike Goulding (mikeegoulding) - https://drupal.org/user/mikeegoulding

This project has been sponsored by:
 * Ashday Interactive
   Building your digital ecosystem can be daunting. Elaborate websites,
   complex cloud applications, mountains of data, endless virtual wires
   of integrations.
