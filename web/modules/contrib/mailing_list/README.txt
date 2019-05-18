MAILING LIST DRUPAL MODULE
==========================

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Requirements
 * Installation
 * Configuration
 * Recommended modules
 * Contact


SUMMARY
-------

This is a multipurpose mailing list module. It provides:

 * a mailing list entity type. Create a bundle for each particular list your
   website needs. Define your own fields that will be available on the
   subscription form.

 * a subscription entity type. Subscriptions are content entities, they will
   be available for views, hooks, etc.

 * double opt-in with email confirmation


REQUIREMENTS
------------

No special requirements needed for single opt-in mailing list. The
subscription confirmation depends on the email_confirmer module available at:

https://www.drupal.org/project/email_confirmer


INSTALLATION
------------

Install the main mailing list module as usual, see:

  https://www.drupal.org/node/1897420

for further information.

The confirmed opt-in is provided by the mailing list confirm submodule. Enable
it and its dependencies if they were not installed.


CONFIGURATION
-------------

Basic steps:

 * create your first mailing list at Structure -> Mailing lists -> Add a new
   mailing list

 * grant the subscribe permission to some roles at People -> Permissions. Also
   enable access email confirmation (provided by email_confirmer) if
   subscription confirmation is enabled

 * add a subscription block in some area at Structure -> Block layout -> Place
   block

 * administer subscriptions at People -> Mailing list subscriptions


RECOMMENDED MODULES
-------------------

 * Views Send (views_send) - https://www.drupal.org/project/views_send
   Send newsletters to your mailing list subscribers with it.

 * Automatic Entity Label (auto_entitylabel) -
   https://www.drupal.org/project/auto_entitylabel
   Hide subscription title/name and fill it with a computed value.

 * CSV Serialization - (csv_serialization) -
   https://www.drupal.org/project/csv_serialization
   To export your mailing list as CSV.

 * Inmail (inmail) - https://www.drupal.org/project/inmail
   Deal with bounces.


CONTACT
-------

Current maintainers:

Drupal 8 version:

 * Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420

Drupal 7 version:

 * Oleg Terenchuk (litwol) - https://www.drupal.org/user/78134
 * Jacob Singh (JacobSingh) - https://www.drupal.org/user/68912
