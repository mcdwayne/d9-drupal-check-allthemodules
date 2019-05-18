CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Features
 * Maintainers


INTRODUCTION
------------

The Charting module allows charts to be integrated into content in a practical
way.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/charting

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/charting


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

Third party libraries:
 * rendro/easy-pie-chart - https://rendro.github.io/easy-pie-chart/
 * chartjs/Chart.js - http://www.chartjs.org/
 * Google Chart Color List -
   http://there4.io/2012/05/02/google-chart-color-list/
 * Moment.js - http://momentjs.com/

About third party libraries:
 * Including 3rd party libraries - https://www.drupal.org/node/2947530
 * Policy on 3rd party assets on Drupal.org - https://www.drupal.org/node/422996


INSTALLATION
------------

 * Install the Charting module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.

This module create the view "Node type usage" by default, in the
URL /node-type-usage.

FEATURES
--------

 * Number formatters with a percentage circle:
   Configurable canvas size.
   Overridable theme with easy_pie_chart_percent_field_formatter theme.
 * Number formatters with a percentage bar:
   Configurable colors and animation.
   Overridable theme with percent_bar_field_formatter theme.
 * Chart views style.
   Customizable value and label fields:
   Dynamic bar width.
   Overridable theme views_style_charting_chart.
   Chart.js charts type: Doughnut, Semi doughnut, Pie, Semi pie and line.


MAINTAINERS
-----------

 * Pedro Pelaez (psf_) - https://www.drupal.org/u/psf_

Supporting organization:

 * SDOS - https://www.drupal.org/sdos
