CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

This very simple module allows users to log in with either their username OR
email address from the same input box on the standard login form.

Note, the system checks a matching email address BEFORE checking a matching
username (email takes priority).


REQUIREMENTS
------------

Core dependencies only.


RECOMMENDED MODULES
-------------------

This module duplicates some functionality present in the 7.x-1.x versions of
the logintoboggon module.
However, at the time of writing that module does not have a functional 8.x-1.x
version.
https://www.drupal.org/node/2147969


INSTALLATION
------------

Install via /admin/modules
or
drush en login_emailorpassword -y


CONFIGURATION
-------------

There are no configuration options.


MAINTAINERS
-----------

https://www.drupal.org/u/rjjakes
