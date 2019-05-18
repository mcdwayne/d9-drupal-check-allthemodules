CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Configuration

INTRODUCTION
------------
This module serve as Payment Gateway provided by Razorpay.

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

I) Using Composer
1. In drupal root composer.json, in merge-plugin include modules/*/composer.json OR modules/custom/*/composer.json.
2. Then run composer update from docroot to include razorpay library in the
docroot.

1.2 Install the module.
2. Go to payment setting under store
2.1. Enter the required keys provided to you by Razorpay.
2.2. For test purpose
3. Select Test or Production based on your use.

MAINTAINERS
-----------

Current maintainers:
 * Jyoti Bohra (nehajyoti).
