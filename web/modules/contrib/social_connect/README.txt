The Basics
==========
Social Connect module allows users to login on a Drupal website 
through the Facebook and Google – using their Facebook or Google login and password.

The module also brings other extra features:

Admin can allow user profile fields update with Facebook and Google data
Admin can configure field mapping and customize login buttons

Differences from other modules:

Flexible
Small and simple
Provide Social Connect Block
TO DO: Basic support Domain Access module


Requirements
============
Core module: field module.


Installation
============
Install as usual, see http://drupal.org/node/70151 for further information.


Settings
========
* admin/config/people/social_connect
Module settings page where you can enable Facebook/Google Connect and configure it.
You need to create new Facebook/Google application or edit existing.
Facebook applocation ID can be found here: https://developers.facebook.com/
Google client ID can be found here: https://console.developers.google.com/


* admin/config/people/social_connect/mapping
Module settings page where you can configure field mapping.
Fields that used for login (Facebook and Google UserID) is recommended to be mapped.
