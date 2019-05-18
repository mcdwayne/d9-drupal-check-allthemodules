CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Notes
* Maintainers


INTRODUCTION
------------

The Date Popup Timepicker module adds more timepicker options for elements
of date_popup type and date_popup widgets for date fields provided by the Date
module (https://www.drupal.org/project/date). The only available option
for now is jQuery UI Timepicker (By François Gélinas) timepicker library, and
provided widget looks very similar to core's jQuery UI Datepicker widget shipped
with Drupal core and utilized by the Date module.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/date_popup_timepicker

 * To submit bug reports and feature suggestions including new timepicker
   plugins support, or to track changes:
   https://www.drupal.org/project/issues/date_popup_timepicker


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Date Popup Timepicker module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

  * Create content type (or other entities bundle) with Date field.
  * Go to your content type "Manage form display" page.
  * Change the widget of this date field to "Timepicker".


NOTES
-----

  * There is known issue in jQuery UI Timepicker (By François Gélinas) library
    when using it with the Bootstrap front-end framework. You'll need to add
    CSS fix to your styles if issue will appear, similar to the following:

    .ui-timepicker-table td a {
      -webkit-box-sizing: content-box !important;
      box-sizing: content-box !important;
    }

    Please see https://github.com/fgelinas/timepicker/issues/86 for more details.


MAINTAINERS
-----------

This module developed by ADCI Solutions team.

  * https://www.drupal.org/adci-solutions

  * http://www.adcisolutions.com
