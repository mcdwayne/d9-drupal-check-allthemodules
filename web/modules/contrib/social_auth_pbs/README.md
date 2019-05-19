CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How it works
 * Support requests
 * Maintainers


INTRODUCTION
------------

Social Auth PBS is a PBS authentication integration for Drupal.


REQUIREMENTS
------------

This module requires the following modules:

 * [Social Auth](https://drupal.org/project/social_auth)
 * [Social API](https://drupal.org/project/social_api)


INSTALLATION
------------

 * Run composer to install the dependencies:
   composer require 'drupal/social_auth_pbs'

 * Install the dependencies: Social API and Social Auth.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Add your PBS project OAuth information in
   Configuration » User Authentication » PBS.

 * Place a Social Auth Login block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

User can click on the PBS logo in the Social Auth Login block. You can also add
a button or link anywhere on the site that points to /user/login/pbs, so
theming and customizing the button or link is very flexible.

When the user opens the /user/login/pbs link, it automatically takes user to
PBS for authentication. PBS then returns the user to Drupal site. If there is an
existing Drupal user with the same email address provided by PBS, that user is
logged in. Otherwise a new Drupal user is created.


SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog.

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/social_auth_pbs

When posting a support request, please inform what does the status report say
at admin/reports/dblog and if you were able to see any errors in
Recent log entries.


MAINTAINERS
-----------

Current maintainers:

 * [Christopher C. Wells (wells)](https://www.drupal.org/u/wells)

Development is sponsored by:

 * [Cascade Public Media](https://www.drupal.org/cascade-public-media)
