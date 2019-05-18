CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The module provides a Condition plugin, checking the status code for 40x range of client errors. 
This can be used be any modules that use the Condition plugins.

Therefore, one simple use case would be to display a block on a 404 Not Found page.

The supported HTTP Client errors page codes are:
 * 401
 * 403
 * 404  


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.


CONFIGURATION
-------------

The Condition will be displayed by supporting modules. 

The most common use case will be for the core Block system.

* Configure a block under Administration > Structure > Block layout
* A vertical tab called HTTP 40x Client errors will be displayed.
* Choose the settings you require for the block. 
  e.g. Check 'Display on 403 Page', if you want the block to be displayed on the
  HTTP 403 Forbidden Client error page.


MAINTAINERS
-----------

Current maintainers:
 * Jeff Logan (jefflogan) - https://www.drupal.org/u/jefflogan
