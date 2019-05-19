CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The module replaces the password based authentication with a one-time login
link. It uses the core drupal authentication process, but removes the standard
username/password login form and replaces it with the password reset form,
which after submission sends out a one-time login link to the requested
email address.

Although it re-uses the core password reset form, wording and submission
workflow is changed to match the actual use-case closely.

REQUIREMENTS
------------

No special requirements


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.


CONFIGURATION
-------------

A site administrator can configure the module at /admin/config/people/token_login:
* Define the email template used when sending out the login link
* Define the session lifetime during which the generated token will be valid
* Optional: whitelist domains that can request login tokens


MAINTAINERS
-----------

Current maintainers:
 * Balazs Dianiska (snufkin) - https://www.drupal.org/user/58645
