INTRODUCTION
------------
Slimbox 2 is a Drupal module that provides a wrapper to integrate
the easy to use Slimbox 2 "lightbox" plugin into Drupal websites.

Once installed, you can make any link open content in a "lightbox"
by setting the rel attribute of your link to rel="lightbox".
Your link should look something like:
<a href="path-to-some-image.png" rel="lightbox">link text or image</a>

For Lightbox Series use rel="lightbox-series":
<a href="path-to-some-image.png" rel="lightbox-series">link text or image</a>


INSTALLATION DRUSH (STILL WORKING ON)
-------------------------------

1) Install this Slimbox2 module: drush en slimbox2 -y

2) Upload the Slimbox2 plugin to /libraries: drush slimbox2-plugin


INSTALLATION MANUALLY
---------------------

1) Get the Slimbox 2 plugin from
https://github.com/cbeyls/slimbox/releases/tag/2.05

2) unpack the Slimbox 2 plugin and rename to slimbox2

3) Upload the slimbox2 plugin to your Drupal site's libraries folder.
   You will need to create one at: /libraries
   Full path with slimbox2: /libraries/slimbox2

4) Install this Slimbox2 module
   https://drupal.org/project/slimbox2

6) Activate Slimbox2


REQUIREMENTS
------------
Drupal 8
Slimbox 2 jQuery plugin


ABOUT THE SLIMBOX2 JQUERY PLUGIN (from the Slimbox 2 web page)
--------------------------------------------------------------
Slimbox 2 is a 4 KB visual clone of the popular Lightbox 2 script
by Lokesh Dhakar, written using the jQuery javascript library.
It was designed to be very small, efficient, standards-friendly,
fully customizable, more convenient and 100% compatible with
the original Lightbox 2.

Slimbox 2 website: http://www.digitalia.be/software/slimbox2


DISCLAIMER
----------
I don't know the originator of the Slimbox 2 jQuery plugin
and have only developed this module
because I have found it simple to use.
I hope others find this module useful and easy to use.

MAINTAINERS
-----------
Current maintainer:
 * Andrew Wasson (awasson) - https://drupal.org/user/127091
