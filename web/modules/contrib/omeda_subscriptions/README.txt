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

The Omeda Subscriptions module extends the Omeda base module to provide users
the ability to manage their deployment Opt Ins and Opt Outs via a new tab on
their user profile. There is a custom permission to determine which roles can
manage their subscriptions and there is also a configuration page to allow you
to enable which deployment opt ins and opt outs are manageable by the user.

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/project/issues/omeda_subscriptions

To access the Omeda API documentation, visit
https://jira.omeda.com/wiki/en/Wiki_Home


REQUIREMENTS
------------

This module requires to Omeda module.


INSTALLATION
------------

Install this module via composer by running the following command:
* composer require drupal/omeda_subscriptions


CONFIGURATION
------------
 Configure Omeda Subscriptions in Administration » Configuration »
 Omeda » Omeda Subscription Configuration

 or by going directly to /admin/config/omeda/subscriptions:

 * Subscriptions Available
 - This allows enabling all or a subset of available Omeda Deployment Types.

RECOMMENDED MODULES
------------

* Omeda (https://drupal.org/project/omeda)
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
   complex cloud applications, mountains of data,
   endless virtual wires of integrations.
