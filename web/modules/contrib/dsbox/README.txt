Drupal Swipebox module; README.txt

The module uses the Swipebox jQuery lightbox to provide formatters for the
core image and link fields.
This makes it possible to display images and YouTube/Vimeo videos in a lightbox.

DEPENDENCIES

Drupal 8.
Swipebox jQuery library: http://brutaldesign.github.io/swipebox
Libraries API module - https://drupal.org/project/libraries
Image - Core module
Link - Core module

INSTALLATION

1. Install the Swipebox jQuery library.

a) Download the Swipebox library and unpack the file.
b) Create the directory libraries/swipebox if it not exist.
c) Copy the needed library files to the directory - see the following structure.

Finally, there MUST exist the following structure:

libraries/swipebox/src/img/icons.png
libraries/swipebox/src/img/icons.svg
libraries/swipebox/src/img/loader.gif
libraries/swipebox/src/js/jquery.swipebox.js
libraries/swipebox/src/js/jquery.swipebox.min.js
libraries/swipebox/src/css/swipebox.css
libraries/swipebox/src/css/swipebox.min.css

2. Install and enable the Libraries module if not already done.

3. Install and enable the Drupal Swipebox module.

ADMINISTER

No module administration available.

USAGE

Configure a core image field - images in the lightbox

Manage the display of an image field and use as format 'Swipebox'.
Click the gear icon and configure the format settings for the Swipebox.

Configure a core link field - videos in the lightbox

As first step,
use the Tab 'Manage form display' and choose the Widget 'Swipebox video link'.
It is a good idea to use a placeholder for the URL. To do this, click the gear
icon and enter a example URL in the field 'Placeholder for URL'.

As second step,
use the Tab 'Manage display' and choose as format 'Swipebox video link'.
Click the gear icon and configure the link text length and the option to group
videos in the Swipebox.

Supported video URL formats

http://www.youtube.com/watch?v=XSGBVzeBUbk
https://www.youtube.com/watch?v=XSGBVzeBUbk
http://youtube.com/watch?v=XSGBVzeBUbk
https://youtube.com/watch?v=XSGBVzeBUbk
http://youtu.be/XSGBVzeBUbk
https://youtu.be/XSGBVzeBUbk
http://vimeo.com/54178821
https://vimeo.com/54178821

THEMING

Two template files are available, located in the module templates folder.

dsbox-image-formatter.html.twig
dsbox-link-formatter-video-link.html.twig

CRON

The module provides an update information for the Swipebox jQuery library.
A detected new Swipebox vendor version will be reported to the:
- Status report page
- Recent log messages (if Database Logging module enabled)
The cron interval to ckeck a new vendor version is set to 14 days,
available as 'dsbox.settings.interval_days'.

LIMITATION

Picture mappings (breakpoints) are currently not supported.
