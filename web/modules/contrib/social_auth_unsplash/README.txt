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

Social Auth Unsplash is a Unsplash authentication integration for Drupal. It
is based on the Social Auth and Social API projects.

It adds to the site:
 * A new url: /user/login/unsplash.
 * A settings form on /admin/config/social-api/social-auth/unsplash.
 * A Unsplash logo in the Social Auth Login block.


REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)


INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require "drupal/social_auth_unsplash:~2.0"

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

 * A more comprehensive installation instruction for Drupal 8 can be found at
   https://www.drupal.org/node/2935878.


CONFIGURATION
-------------

 * Add your Unsplash OAuth2 application information in
   Configuration » User Authentication » Unsplash.

 * Place a Social Auth Login block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

User can click on the Unsplash logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points
to /user/login/unsplash, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/unsplash link, it automatically takes
user to Unsplash for authentication. Unsplash then returns the user to
Drupal site. If we have an existing Drupal user with the same email address
provided by Unsplash, that user is logged in. Otherwise a new Drupal user is
created.


SUPPORT REQUESTS
----------------

Before posting a support request, carefully read the installation
instructions provided in module documentation page.

Before posting a support request, check Recent log entries at
admin/reports/dblog

Once you have done this, you can post a support request at module issue queue:
https://www.drupal.org/project/issues/social_auth_unsplash

When posting a support request, please inform if you were able to see any errors
in Recent log entries.


MAINTAINERS
-----------

Current maintainers:
 * Krzysztof Krawczyk (kkrawczyk) - https://www.drupal.org/u/pllvsh
 * Getulio Sánchez (gvso) - https://www.drupal.org/u/gvso
