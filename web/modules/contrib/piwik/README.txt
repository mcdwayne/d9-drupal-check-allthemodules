
Module: Piwik - Web analytics
Author: Alexander Hass <http://drupal.org/user/85918>


Description
===========
Adds the Piwik tracking system to your website.

Requirements
============

* Piwik installation
* Piwik website ID


Installation
============
* Copy the 'piwik' module directory in to your Drupal 'modules'
directory as usual.


Usage
=====
In the settings page enter your Piwik website ID.

All pages will now have the required JavaScript added to the
HTML footer can confirm this by viewing the page source from
your browser.

Page specific tracking
====================================================
The default is set to "Add to every page except the listed pages". By
default the following pages are listed for exclusion:

/admin
/admin/*
/batch
/node/add*
/node/*/*
/user/*/*

These defaults are changeable by the website administrator or any other
user with 'Administer Piwik' permission.

Like the blocks visibility settings in Drupal core, there is a choice for
"Add if the following PHP code returns TRUE." Sample PHP snippets that can be
used in this textarea can be found on the handbook page "Overview-approach to
block visibility" at http://drupal.org/node/64135.

Custom variables
=================
One example for custom variables tracking is the "User roles" tracking. Enter
the below configuration data into the custom variables settings form under
admin/config/system/piwik.

Slot: 1
Name: User roles
Value: [current-user:piwik-role-names]
Scope: Visitor

Slot: 1
Name: User ids
Value: [current-user:piwik-role-ids]
Scope: Visitor

More details about custom variables can be found in the Piwik API documentation
at http://piwik.org/docs/javascript-tracking/#toc-custom-variables.


Advanced Settings
=================
You can include additional JavaScript snippets in the custom javascript
code textarea. These can be found on various blog posts, or on the
official Piwik pages. Support is not provided for any customisations
you include.

To speed up page loading you may also cache the Piwik "piwik.js"
file locally.

Known issues
============
Drupal requirements (http://drupal.org/requirements) tell you to configure 
PHP with "session.save_handler = user", but your Piwik installation may
not work with this configuration and gives you a server error 500.

1. You are able to workaround with the PHP default in your php.ini:

   [Session]
   session.save_handler = files

2. With Apache you may overwrite the PHP setting for the Piwik directory only.
   If Piwik is installed in /piwik you are able to create a .htaccess file in
   this directory with the below code:

   # PHP 4, Apache 1.
   <IfModule mod_php4.c>
     php_value session.save_handler files
   </IfModule>

   # PHP 4, Apache 2.
   <IfModule sapi_apache2.c>
     php_value session.save_handler files
   </IfModule>

   # PHP 5, Apache 1 and 2.
   <IfModule mod_php5.c>
     php_value session.save_handler files
   </IfModule>
