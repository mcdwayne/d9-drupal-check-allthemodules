INTRODUCTION
------------

The compact date/time range formatter module provides the ability to display
date/time range fields in a more compact form, by omitting the month or year
where it would be duplicated.

Examples:
 - 24–25 January 2017 (start & end dates are within the same month)
 - 29 January–3 February 2017 (start & end dates are within the same year)
 - 9:00am–4:30pm, 1 April 2017 (start & end times are both on the same day)

For a full description of the module, visit the project page:
https://drupal.org/project/daterange_compact

To submit bug reports and feature suggestions, or to track changes:
https://drupal.org/project/issues/daterange_compact


REQUIREMENTS
------------

This module requires the experimental 'Datetime range' core module to
be enabled.


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
for further information.


CONFIGURATION
-------------

Date/time range formats are configurable in Administration » Regional and
language » Date and time range formats.

For each format, the following options are available:

  - A default date-only pattern. This is used where the type of field is a
    date range (without time) and the date should be shown in full.
    This pattern is used if the range is a single day or cannot be shown
    in a more compact form. This pattern is required.

  - Optional date-only patterns for the start and end dates where the
    start and end dates are in the same month. You may wish to prevent
    the month and or year from being displayed twice here.

  - Optional date-only patterns for the start and end dates where the
    start and end dates are in the same year. You may wish to prevent
    the year from being displayed twice here.

  - A default date/time pattern. This is used where the type of field is
    a date and time range, and the value should be shown in full.
    This pattern is used if the range is a single time, spans multiple
    days, or if there is no same-day pattern available.

  - Optional same-day pattern for date/time ranges where the start and
    end occur on the same day. You may wish to prevent the date from
    being displayed twice here.

  - Separator strings that are placed in between the start and end values.

All patterns follow the same conventions as Drupal core's date formats,
and use the PHP date/time formatting tokens as described at:
http://www.php.net/manual/en/function.date.php


MAINTAINERS
-----------

Current maintainers:
 * Erik Erskine (erik.erskine) - https://drupal.org/u/erikerskine
