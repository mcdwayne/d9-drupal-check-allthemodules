CONTENTS OF THIS FILE
---------------------

* Overview
* Features
* Requirements
* Installation
* Known problems
* Version history
* Future plans
* Similar projects
* Credits

OVERVIEW
--------

This module aims to let you use Pagawa's PgwSlideshow to display images in an
imagefield.

Why another image slideshow? Why not use the The Field Slideshow module? It
turns out that Field Slideshow doesn't do a great job of adapting to the width
of the browser window (it's not very "responsive" in the "responsive design"
sense). I needed a field slideshow that could do that.

FEATURES
--------

As of version 1.x, you can:

* Put multiple slideshows on the same page, each with different settings,
* Change the following settings:
    * Choose between the minified or debugging variant of the library,
    * Dark or light variant (note the light variant is currently broken),
    * Choose whether or not to display the list of carousel elements,
    * Choose whether or not to display the previous / next controls,
    * Choose whether to bind Javascript touch events so phones / tablets can
      use the slideshow,
    * Set a maxmimum height for the slideshow,
    * Choose between sliding or fading transitions,
    * Change the time to spend adjusting the size of the slideshow,
    * Change the time to spend transitioning between slides,
    * Choose to automatically transition between gallery image, and the time to
      spend on each slide.

REQUIREMENTS
------------

* Field (in core), Image (in core), the Libraries API module
  (http://drupal.org/project/libraries), and the jQuery Update module
  (https://www.drupal.org/project/jquery_update).
* PgwSlideshow version 2.0.x (https://github.com/Pagawa/PgwSlideshow).

INSTALLATION
------------

1. Download, install, and enable the Libraries API project from
   https://www.drupal.org/project/libraries

   See https://drupal.org/node/895232 for further information.

2. Download the PgwSlideshow library from
   https://github.com/Pagawa/PgwSlideshow/releases and extract it to a folder
   named PgwSlideshow in sites/all/libraries (i.e.: so that the full path to
   pgwslideshow.js is sites/all/libraries/PgwSlideshow/pgwslideshow.js).

   See https://www.drupal.org/node/1440066 for further information.

3. Download, install, and enable the jQuery Update project from
   https://www.drupal.org/project/jquery_update

   See https://drupal.org/node/895232 for further information.

4. In the jQuery Update settings page at Administration → Configuration →
   Development (admin/config/development/jquery_update), set the Default jQuery
   Version to 1.7 or higher.

5. Download, install, and enable the Field PgwSlideshow project.

   See https://drupal.org/node/895232 for further information.

6. Go to the "Manage display" tab for any content type, comment, vocabulary, or
   user account that has an image field. In the image field's row, select
   "PgwSlideshow" from the "Format" select-box, and click the "Save" button.

   The "Standard" installation profile's "Article" content-type comes with an
   image field, however, you can only upload one image to it because it's
   "Number of values" setting is set to "1" by default. You can change this by
   clicking "edit" in the image field's row on the the Article content type's
   "Manage fields" page.

   PgwSlideshow will display a slideshow for fields where only 1 image can be
   uploaded, however, it will be a pretty boring slideshow with just one image!

7. Click the "Settings" gear button in the image field's row to change the
   settings for that field's slideshow.

   If you are debugging the PgwSlideshow JavaScript on a page, then it may help
   to de-select the "Use the library's minified version" checkbox. Otherwise,
   you should leave it enabled. Minified source code reduces the amount of data
   that needs to be transferred, which speeds up the front-end of your site.

   You can select a dark or light style for the slideshow by changing the
   "Slideshow style" select-box.

   To show or hide the image thumbnails below the slideshow, toggle the
   "Display list of carousel elements" checkbox.

   To show or hide the previous and next arrows overlaid on the left- and
   right-hand sides of the image, toggle the "Display previous / next controls"
   checkbox.

   To prevent users who aren't using a mouse or keyboard from changing between
   slides, de-select the "Allow touch controls to change between slides"
   checkbox. You probably want to leave this turned on unless manual testing
   reveals that other elements on the page interfere with the slideshow
   controls.

   To constrain the slideshow to a maximum height, enter a number of pixels in
   the "Maximum height" textfield. If you do not want to constrain the slideshow
   to a maximum height, empty this field.

   To change the visual effect used to change between slides, set the
   "Transition effect" select-box. Only "Sliding" and "Fading" are supported by
   the PgwSlideshow JavaScript library at this time.

   When transitioning between images of different heights, the PgwSlideshow
   library can be configured to spend some time smoothly adjusting the height of
   the slideshow. To change the time spent doing this, change the "Adaptive
   duration" textfield. Higher numbers result in slower animations, and only
   numbers greater than 0 are allowed. Note that constraining the slideshow to a
   maximum height will prevent this animation from occurring.

   The PgwSlideshow library can be configured to spend a certain amount of time
   transitioning between images (this occurs after the adaptive duration
   animation has completed). To change the time spent doing this, change the
   "Transition duration" textfield. Higher numbers result in slower animations,
   and only numbers greater than 0 are allowed.

   To have the PgwSlideshow automatically transition between images, check the
   "Automatically transition between gallery images" checkbox and set the time
   spent on each slide by changing the "Interval duration" textfield. Higher
   numbers result in a longer time spent on each slide, and only numbers greater
   than 0 are allowed.

KNOWN PROBLEMS
--------------

I don't know of any problems at this time, so if you find one, please let me
know by adding an issue at https://www.drupal.org/node/add/project-issue/2477947

VERSION HISTORY
---------------

The 1.x branch supports PgwSlideshow version 2.0.x.

FUTURE PLANS
------------

I plan to keep this module working with the latest version of PgwSlideshow.
Other than that, I don't really have any new features planned.

SIMILAR PROJECTS
----------------

* The Field Slideshow module does something similar, but uses the jQuery Cycle
  plugin instead.

CREDITS
-------

MallDatabase.com helped mparker17 to come up with the concept and work on the
module.
