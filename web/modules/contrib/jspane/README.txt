SUMMARY - JscrollPane
========================

JscrollPane is a very simple Drupal module to implement the jScrollPane.
jScrollPane is a cross-browser jQuery plugin by Kelvin Luck 
(http://jscrollpane.kelvinluck.com) which converts a browser's default 
scrollbars into an HTML structure which can be easily skinned with CSS.

Installation:
-------------

Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8

Download the below files from http://jscrollpane.kelvinluck.com/#download
and put under libraries folder
 jquery.jscrollpane.min.js
 jquery.mousewheel.js
 jquery.jscrollpane.css

Library path should be:
DRUPAL_ROOT . /libraries/jscrollpane/jquery.jscrollpane.min.js
DRUPAL_ROOT . /libraries/jscrollpane/jquery.mousewheel.js
DRUPAL_ROOT . /libraries/jscrollpane/jquery.jscrollpane.css

Configuration:
--------------

Go to admin/config/jscrollpane/settings and configure as you want.
For more information on how to use the jScrollPane() parameters please refer to
the jScrollPage settings page (http://jscrollpane.kelvinluck.com/settings.html).

Usage:
------

/* Call jScrollPane library */
jQuery('.scroll-pane').jScrollPane(); // Default Scrollbar
jQuery('.scroll-pane').jScrollPane({ showArrows: true }); // Scrollbar options

/* Add the below CSS in your custom file */
.scroll-pane
{
    width: 100%;
    height: 200px; 
    overflow: auto;
}

/* Add Horizontal Bar */
.horizontal-only
{
    height: auto;
    max-height: 200px;
}

Support:
--------

https://www.drupal.org/u/vedprakash
