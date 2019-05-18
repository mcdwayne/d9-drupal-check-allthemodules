CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Flot module provides an API and views integration for using the flot
graphing library. It is designed to make it simple to add flot graphs or charts,
it supports line, bar and pie charts.

The Flot object contains series data, and chart options. The module's element
scans these to determine which javascript libraries to include. The plot can be
rendered using an included bare-bones template, or the developer can create
their own for more complex logic and layouts. The flot_examples module
demonstrates basic and more advanced techniques.

 * For a full description of the module visit:
   https://www.drupal.org/project/flot or
   http://code.google.com/p/flot/

 * For a Flot usage example visit:
   http://drupal.org/node/386484

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/flot


REQUIREMENTS
------------

 * [Flot JS library](https://github.com/flot/flot)


INSTALLATION
------------

Install the Flot module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Extract the [Flot JS library](https://github.com/flot/flot) to
       `/DrupalRoot/libraries/flot`
       (i.e. `/DrupalRoot/libraries/flot/jquery.flot.pie.js`)
    2. Navigate to Admin>Extend to install Flot and any relevant Flot
       sub-modules (Timeseries, Spider Chart, etc.)


Developers can use this module in either of two ways, using the default
render element, or creating a new render element.

Views Formatters:
The Drupal 8 module also contains two views formatters. These can be used to
render views as a pie chart, bar chart, or time-series line or scatter plot
without the need to type a single line of code.

Plugins:
Flot has been extended by many developers through the creation of other plugins.
These plugins can be easily enabled by creating a simple wrapper around the
existing JavaScript code and creating a new Element. The flot_spider module
demonstrates this process.


MAINTAINERS
-----------

 * Beakerboy - https://www.drupal.org/u/beakerboy
 * Jelle Sebreghts (Jelle_S) - https://www.drupal.org/u/jelle_s
 * Peter Droogmans (attiks) - https://www.drupal.org/u/attiks
 * yhahn - https://www.drupal.org/user/264833
 * Jeff Miccolis (jmiccolis) - https://www.drupal.org/user/31731
