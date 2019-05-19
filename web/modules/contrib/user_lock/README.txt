CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Installation
 * Configuration
 * Usage

INTRODUCTION
------------
 * URL Lock module provides you to lock user login.
 * Prevents User(s) to login for particular time period.
 * Checks the action for login and redirects as per user.
 * Provides URL Lock Admin UI to add/edit/delete all User lock(s).
 * Can give time period i.e from and to dates to lock user.
 * User Lock works with the latest saved lock period for that user.

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module.
   See: https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
 * After installing goto http://YOURSITE/admin/structure/user_lock_config_entity/add.
 * You can select user(s) in select list, lock period from and to, message, redirect URL.
 * Also you can edit/delete selected user lock.

 USAGE
 -----
 *You need USER LOCK if:
  -> You have a variety of users and you want lock them for particular time period upon login. The module allows you to customize the destination that a user is redirected to after lock period is set.
  -> You want to have a custom message for different types of users upon USER LOCK.
