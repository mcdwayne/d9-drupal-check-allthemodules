CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * SETUP & USAGE
 * Support requests
 * Maintainers


INTRODUCTION
------------

Key auth provides simple key-based authentication on a per-user
basis similar to basic_auth but without requiring
usernames or passwords.

This is ideal for sites that expose consumer-facing APIs
via rest, jsonapi, or something similar.

For a full description of the module, visit the project page:
https://www.drupal.org/project/key_auth


INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require 'drupal/key_auth:^1.0'

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8
   /installing-drupal-8-modules for further information.

 * A more comprehensive installation instruction for
   Drupal 8 can be found at https://www.drupal.org/node/2923804/


SETUP & USAGE
-------------

 * Grant users the 'Use key authentication' permission.
 * Configure the basic settings at admin/config/services/key-auth.
 * Users with adequate permissions can view/update/delete their key at
   user/{user}/key-auth.
 * To use with core rest, enable the key_auth authentication provider for your
   endpoints of choice.
 * To use with jsonapi, no additional configuration is required.
 * If Header detection is enabled, pass in a header with the name chosen in the
   configuration, and a value of your user's key
   (ie, api-key: b9a9a0ee50ceab7191282b51c).
 * If Query detection is enabled, include a query parameter in the endpoint URL
   with the name chosen in the configuration, and a value of your user's key
   (ie, ?api-key=b9a9a0ee50ceab7191282b51c).


SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/key_auth

When posting a support request, please inform if you were able to see any errors
in Recent log entries.


MAINTAINERS
-----------

Current maintainers:
 * mstef - https://www.drupal.org/u/mstef
