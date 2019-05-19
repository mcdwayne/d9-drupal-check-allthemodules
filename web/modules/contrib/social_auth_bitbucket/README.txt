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

Social Auth Bitbucket is a Bitbucket authentication integration for Drupal. It
is based on the Social Auth and Social API projects.

It adds to the site:
 * A new url: /user/login/bitbucket.
 * A settings form on /admin/config/social-api/social-auth/bitbucket.
 * A Bitbucket logo in the Social Auth Login block.


REQUIREMENTS
------------

This module requires the following modules:

 * Social Auth (https://drupal.org/project/social_auth)
 * Social API (https://drupal.org/project/social_api)


INSTALLATION
------------

 * Run composer to install the dependencies.
   composer require "drupal/social_auth_bitbucket:^2.0"

 * Install the dependencies: Social API and Social Auth.

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Add your Bitbucket project OAuth information in
   Configuration » User Authentication » Bitbucket.

 * Place a Social Auth Bitbucket block in Structure » Block Layout.

 * If you already have a Social Auth Login block in the site, rebuild the cache.


HOW IT WORKS
------------

User can click on the Bitbucket logo on the Social Auth Login block
You can also add a button or link anywhere on the site that points
to /user/login/bitbucket, so theming and customizing the button or link
is very flexible.

When the user opens the /user/login/bitbucket link, it automatically takes the
user to Bitbucket Accounts for authentication. Bitbucket then returns the user
to Drupal site. If we have an existing Drupal user with the same email address
provided by Bitbucket, that user is logged in. Otherwise a new Drupal user is
created.


SUPPORT REQUESTS
----------------

 * Before posting a support request, carefully read the installation
   instructions provided in module documentation page.

 * Before posting a support request, check Recent Log entries at
   admin/reports/dblog

 * Once you have done this, you can post a support request at module issue
   queue: https://www.drupal.org/project/issues/social_auth_bitbucket

 * When posting a support request, please inform if you were able to see any
   errors in the Recent Log entries.


MAINTAINERS
-----------

Current maintainers:
 * Levi Govaerts (legovaer) - https://www.drupal.org/u/legovaer
 * Getulio Sanchez (gvso) - https://www.drupal.org/u/gvso
