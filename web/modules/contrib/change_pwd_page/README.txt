CONTENTS OF THIS FILE
=====================

 * Introduction
 * Requirements
 * Installation
 * Usage
 * Author
 * Maintainer

INTRODUCTION
============
The Password Separate Form module provides the separate password change form.
By default it comes with user account page that little bit confusing for end
users. This module would help to make this form as a separate form to help end
users, there is no need to change these fields every time if you are editing
some other fields on user account page.

REQUIREMENTS
------------

 * Password Separate Form uses the following Drupal 8 Core components:
     User, Password.

 * There are no special requirements outside core.

INSTALLATION
============

* Install as you would normally install a contributed Drupal module. See:
     https://drupal.org/documentation/install/modules-themes/modules-8
     for further information.

USAGE
=====
  * Go to People page and edit any user account. You can also access directly
    user page, if you know user uid using /user/%uid/edit. Here %uid is user
    uid.
  
  * Once you will click on edit link, you will find there is no Password related
    fields, one additional tab is there Change Password.
  
  * Click on the "Change Password" tab you will see now you have separate form
    to manage password.

AUTHOR
======
Gaurav Pahuja (https://www.drupal.org/u/gaurav.pahuja)

Maintainer
======
Pushpinder Rana (https://www.drupal.org/u/er.pushpinderrana)

This README has been completely re-written for Scheduler 8.x, based on the
template https://www.drupal.org/node/2181737 as on Oct 2016
