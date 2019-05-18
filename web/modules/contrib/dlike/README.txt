CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration

INTRODUCTION
------------

This module provides Facebook-like "Like" functionality using the Flag and
Lightbox2 modules.

It appends a string to the flag link that displays the number of time a piece of
content has been flagged. Different strings for different flags can be
configured. If Lightbox module is enabled, the count would show up in the modal
window.


REQUIREMENTS
------------

This module requires the following modules:

*Flag (http://drupal.org/project/flag):

Since this module majorly depends on the Flag module, the version numbering of
this module follows that of the Flag module. So 8.x-1.x of this module works
with 8.x-4.x of the Flag module.

*Views(http://drupal.org/project/views):

This module depends on Views to display a list of users who have flagged
content.


RECOMMENDED MODULES
-------------------

*Lightbox2(http://drupal.org/project/lightbox2):

Without Lightbox2, the list of users who have flagged content will show on a
separate page in plain HTML.The 7.x-2.10 version of this
module improves the default behavior to use CTools to display a modal when
Lightbox2 is not present. However,this version of this module has not been
designed to work with Drupal 8, nor is there any plans for porting this
module to Drupal 8. Colorbox 8.x-1.2(https://www.drupal.org/project/colorbox)
has been suggested as an alternative, BUT SO FAR NOBODY HAS TESTED/CONFIRMED
THAT IT WORKS WITH Dlike 8.x-1.x YET.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
 See: https://goo.gl/2En3aT for further information.

 * You may want to disable Toolbar module since its output clashes with
Administration Menu.


CONFIGURATION
-------------

*Enable module on the module's page

-Go to the edit page of the desired flag. At the bottom of flag settings, enable
Drupal like and configure the strings.

*Enable Lightbox (Highly recommended)

-If Lightbox is enabled on the site, the count would open in a modal window,
else it would redirect to a blank page with the list of users who have flagged
that content.

*Admin may set permissions to restrict the viewing of users list. If a user is
not allowed to access the list of users, he would only see the count.

----------------------------------------------------------------------------------------------
