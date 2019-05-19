DRUPAL SPLASHIFY MODULE
------------------------
Maintainers:
  Chris Roane (http://drupal.org/user/1283000)
Contributors:
  Drudesk (https://www.drupal.org/drudesk)
Requires - Drupal 8, Library, Field Group and Colorbox.
License - GPL (see LICENSE)


1.0 OVERVIEW
-------------
Splashify is a full featured splash page module that is designed to be search
engine friendly. It is originally based on the Drupal 6 Splash module. It
allows you to specify a page to be displayed anywhere on your site, using one
of a few different delivery options (redirect, popup window or ColorBox).

The main focus of this module is the following:
- Be search engine friendly by redirecting via JavaScript (when applicable).
- Allow different ways in delivering the splash page.
- Use ColorBox for displaying the splash page in a lightbox.
- You can have a list of splash pages show up in a specified order, display the
specified text/html in the site template or display the text/html full screen.

All of the features of this module have been confirmed to work in FF, Chrome,
Safari and IE7 through IE9.


2.0 INSTALLATION
-----------------
1. Download and enable the "Libraries" Drupal module. 
Link: http://drupal.org/project/libraries

2. Download and enable the "Colorbox" Drupal module. 
Link: http://drupal.org/project/colorbox

3. Download and enable the "Field Group" Drupal module. 
Link: http://drupal.org/project/field_group

4. Download and enable the latest version of the Splashify module.
Link: http://drupal.org/project/splashify

5. Verify there are no splashify errors on the Status report page
(admin/reports/status).


2.1 CONFIGURATION
------------------
Go to "Structure" -> "Splashify group entity list" -> "Add Splashify group entity"
to create group with configuration for splash.

Go to "Structure" -> "Splashify entity list" -> "Add Splashify entity"
to create splash.

Go to "People" -> "Permissions". Make sure the "Splashify" section has the
correct permissions in who can administer splash groups and splash entities.


3.0 PROBLEMS OR FEATURE REQUESTS
---------------------------------
First make sure an issue doesn't already exist. If it doesn't, create a new
issue: http://drupal.org/project/issues/splashify


SPONSORS
--------
This module has been sponsored by The Brick Factory (thebrickfactory.com).
