CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Appointment Calendar is a simple form to set and create slots for booking on a
particular date.

It provides a simple availability calendar to check the availability of a
selected slot, i.e, all available/booked slots can show in the availability
calendar.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/appointment_calendar

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/appointment_calendar


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Calendar - https://www.drupal.org/project/calendar


INSTALLATION
------------

 * Install the Appointment Calendar module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. After installation of the module clear all the caches. (Important to do
       this).
    3. This module will import a View block and create an Appointment Calendar
       content type (Do not modify the View that has been imported by the
       module. It is highly recommended to use as is).
    4. Navigate to /admin/config/appointment-calendar/settings and select
       appointment to and from dates and number of slots available. Select
       "Fill Slots" and the time slots will open in a list for editing.
    5. In the list you can edit the particular date and are able to change
       slots times and the capacity of slots. Submit.
    6. Navigate to Administration > Structure > Content types > Appointment
       Calendar > Manage fields to add additional fields.
    7. Navigate to Administration > Content > Add Content to create an
       Appointment Calendar node.


MAINTAINERS
-----------

Current maintainers:

 * A AjayKumar Reddy (ajay_reddy) - https://www.drupal.org/u/ajay_reddy
