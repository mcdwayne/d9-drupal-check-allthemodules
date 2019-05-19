CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Timeago is a light-weight, customizable plugin for jQuery 1.4.3+.
This module allows for integration of Timeago into Drupal.
The jQuery library is a part of Drupal since version 7+.

* jQuery - http://jquery.com/
* Timeago - https://timeago.yarp.com/


FEATURES:
---------

The Sinceago module:

* Works as a Formatter in dates for node and comments.
* Write text as your owen value using configuration form.
* Drush command, drush sinceago-plugin, to download and install the Sinceago
  plugin in "libraries/".

The Timeago plugin:

* Compatible with: jQuery 1.3.2+ in Firefox, Safari, Chrome, Opera, Internet
  Explorer 7+
* Lightweight: 10KB of JavaScript (less than 5KBs gzipped).
* Appearance is controlled through CSS so it can be restyled.
* Can be extended with parameters and values without altering the source
  files.
* Completely unobtrusive, options are set in the JS and require no changes to
  existing HTML.
* Released under the MIT License.


REQUIREMENTS
------------

Just Timeago plugin in "libraries".


INSTALLATION
------------

1. Install the module as normal, see link for instructions.
   Link: https://www.drupal.org/documentation/install/modules-themes/modules-8

2. Download and unpack the Sinceago plugin in "libraries".
    Make sure the path to the plugin file becomes:
    "libraries/sinceago/jquery.timeago.js"
   Link: https://timeago.yarp.com/jquery.timeago.js
   Drush users can use the command "drush sinceago-plugin".

3. Go to "Administer" -> "Extend" and enable the Sinceago module.


CONFIGURATION
-------------

 * Go to "Configuration" -> "User interface" -> "Sinceago" to find all the configuration
   options.

Add a Sinceago formate to your Dates:
----------------------------------------

Go to "Configuration" -> "User interface" -> "Sinceago" and select "sinceago" for 
node and comments dates.


Drush 8.1.x:
------
A Drush command is provides for easy installation of the Sinceago plugin itself.

% drush sinceagoplugin

The command will download the plugin and unpack it in "libraries/".
It is possible to add another path as an option to the command, but not
recommended unless you know what you are doing.


MAINTAINERS
-----------

Current maintainers:

 * Arulraj M(arulraj) - https://www.drupal.org/u/arulraj

Requires - Drupal 8
License - GPL (see LICENSE)
