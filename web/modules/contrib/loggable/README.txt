INTRODUCTION
------------

This module integrates your Drupal site with Loggable.io. Loggable is a web
service for centralizing and storing administrative events as well as providing
rule-based alerts and notifications. For more information about Loggable visit
https://loggable.io. Watchdog events that match your specified criteria will
be automatically sent to your Loggable account.

For a full description of the module, visit the project page:
https://www.drupal.org/project/loggable


INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require 'drupal/loggable:^1.0'

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8
   /installing-drupal-8-modules for further information.


SETUP & USAGE
-------------

 * Create an account with Loggable (https://loggable.io) if you have not yet.
 * Navigate to the module settings (/admin/config/development/loggable) and
   enter your Loggable account API key and the ID of the channel you want to log
   events to.
 * Navigate to the filters tab (/admin/config/development/loggable/filters) and
   create filters to determine which watchdog events should be captured and sent
   to Loggable. If an event does not match a filter, it will be discarded.


SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/loggable

When posting a support request, please inform if you were able to see any errors
in Recent log entries.

If you require support for the Loggable service, please visit
https://loggable.io for instructions.
