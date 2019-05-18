CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
Automatic User Names disables the "Username" field on user registration and
user edit forms and generates a username automatically using a token or module
provided pattern.The pattern could therefore be based on other profile fields
(like first/last name), a regex version of their email address or a random
string.

This module is complimented by logintobogan, which allows users to login with
their email address (and therefore makes usernames redundant for the user,
but of course still necessary for Drupal's backend - where auto_username fits
in). The realname module also compliments this module, because it ensures any
username displays are displayed as "Firstname Surname", which may be easier to
read.

REQUIREMENTS
------------

This module requires the following modules:

 * Token - https://www.drupal.org/project/token

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

Automatic User Names Administration Configurations:

* Module configuration link : admin/config/people/accounts/patterns.

* Pattern for username -
  Enter your preferred username pattern eg: [user:mail] to generate Usernames
  having email id. To generate random username leave the field blank.

* Evaluate PHP in pattern -
  Enter php code to generate custom username
  eg: <?php echo "[user:roles] Test " . $account->id();?>.
  (IMPORTANT: While using php evaluation use php tags <?php ?>).

MAINTAINERS
-----------

Current maintainers:
 * Alex Bergin (alexkb) - https://www.drupal.org/u/alexkb.
 * Jyoti Bohra (nehajyoti) - https://www.drupal.org/u/nehajyoti.
