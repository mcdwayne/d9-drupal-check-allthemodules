-= PHP Console Drupal module =-

Summary
=========================
This module provides integration with PHP Console server library. PHP Console
allows you to handle PHP errors & exceptions, dump variables, execute PHP code
remotely and many other things using Google Chrome extension PHP Console. You
can use pc() function which pretty prints any kinds of PHP variables.

Requirements
=========================
 * PHP Console extension must be installed on Google Chrome.
 * PHP Console server library should be installed to the vendor directory.

Installation
=========================
 * If you install the module with composer the dependencies will be managed
   automatically. Otherwise you need to install PHP Console library manually
   through composer as follows: `composer require php-console/php-console`.
 * Navigate to PHP Console settings page: /admin/config/development/php-console.
 * Set up your password and IP address in authorization section.
 * Check the module permissions: /admin/people/permissions#module-pc.

Links
=========================
 * Project page: https://drupal.org/project/pc
 * PHP Console home page: http://php-console.com
 * PHP Console server library: https://github.com/barbushin/php-console
 * PHP Console Chrome extension library:
   https://chrome.google.com/webstore/detail/php-console/nfhmhhlpfleoednkpnnnkolmclajemef
 * PHP Console video presentation: http://www.youtube.com/watch?v=_4kG-Zrs2Io
