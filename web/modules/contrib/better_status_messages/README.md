CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module improves the Drupal status messages by providing a default styling
and a close button.

The background color of the status messages can be adjusted via a settings form.


REQUIREMENTS
------------

This module has no other requirements outside of Drupal core.


INSTALLATION
------------

Install the better_status_messages module as you would normally install a
contributed Drupal
module:
- require the repository:
```
composer require drupal/better_status_messages --prefer-dist
```
- enable the module:
```
drush en better_status_messages -y
```


CONFIGURATION
--------------

You can just enable the module and it works.
If you would like to change the background color of the status messages,
you can visit the settings page at:
Configuration > Development > Better Status Messages


MAINTAINERS
-----------

The 8.x.1.x branch was created by:

 * Joery Lemmens (flyke) - https://www.drupal.org/u/flyke
