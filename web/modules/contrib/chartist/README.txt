CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Usage
 * Further information


INTRODUCTION
------------

This is an API module that creates charts based on data provided by the user.


REQUIREMENTS
------------

This module requires the Chartist library:

 * https://gionkunz.github.io/chartist-js/.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.
 * Download the Chartist library (https://gionkunz.github.io/chartist-js/)
   and place files:
    - chartist.min.css
    - chartist.min.js
   in /sites/all/libraries/chartist folder.


USAGE
-----

For a fast tutorial, see examples provided in
example/chartist.example.inc file and accessed on
/chartist/example page.


Implementation of the Chartist theme variables:

 * title - chart title

 * chart_type - type of the chart (refer to Chartist documentation for
   a list of types supported)

 * data - data array. Consists of:
    - series - each serie is an array with following elements:
       * name - serie name
       * data - array of point values
    - labels - array of label (y) values for each point
    - featured_points - array of point coordinates.
      Each coordinate is a two-value array: array($s, $x),
      where $s is a serie index and $x is a point index.
      $x'd point from that serie will be given class .featured
      and can be styled separately

 * settings - settings array. There are following settings:
    - tooltip_schema - chema for tooltips.
      Available placeholders:
        * [x] - x (label) of the point,
        * [y] - y (value) of the point,
        * [serie] - serie name of the point.
    - wrapper_class - class of the chart wrapper element

 * prefix, suffix - as in most theme implementations - HTML that
    is placed directly before and after the actual element.

 * classes - chart element classes array.
    For chart aspect ratio, use classes as in Chartist documentation:

    class             ratio
    ct-square	        1
    ct-minor-second	  15:16
    ct-major-second	  8:9
    ct-minor-third	  5:6
    ct-major-third	  4:5
    ct-perfect-fourth	3:4
    ct-perfect-fifth	2:3
    ct-minor-sixth	  5:8
    ct-golden-section	1:1.618
    ct-major-sixth	  3:5
    ct-minor-seventh	9:16
    ct-major-seventh	8:15
    ct-octave	        1:2
    ct-major-tenth	  2:5
    ct-major-eleventh	3:8
    ct-major-twelfth	1:3
    ct-double-octave	1:4 

 * html - bool indicating if the chart title and serie label
   values should be sanitized before output to browser.
   Defaults to FALSE (sanitize).
  

FURTHER INFORMATION
-------------------

Chartist library documentation can be found here:

https://gionkunz.github.io/chartist-js/getting-started.html
https://gionkunz.github.io/chartist-js/api-documentation.html
