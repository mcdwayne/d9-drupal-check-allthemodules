CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration

INTRODUCTION
------------

 * Display pop-up message after user logged in.
 * Provide configurable back-end to write message.
 * Showing popup when user is logged in, Only once per login.
 * Provide Three types of pop-up messages
  * Welcome pop-up after first time login.
  * Always show pop-up after logged in.
  * 'Pop-up after first time login' and'Always show pop-up after logged in'.

REQUIREMENTS
------------

 * This module will depends upon sweetalert2 module.
 * <https://www.drupal.org/project/sweetalert2>.
 * This will use sweetalert2 plug-in for pop-up functionality.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.

CONFIGURATION
-------------

 * Go to admin/config/popup_after_login.
 * Configure it by selecting Role which you want to show pop-up message.
 * Enter title, message body and save. 
 * Clear cache and login with role that you selected.
