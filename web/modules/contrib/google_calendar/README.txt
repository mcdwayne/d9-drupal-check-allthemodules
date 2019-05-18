CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Google Calendar Import module provides a way to import Google Calendar
events from a publicly available calendar into Drupal.

Once into Drupal, you can layer on additional fields, theming, access control,
and all the other things that make Drupal Entities so excellent to work with.

 * More information is available at the project page:
   https://www.drupal.org/project/google_calendar

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/google_calendar

The module creates two new entity types - one for Calendars as a whole, and
one for Events (entries) in those Calendars. Each Calendar has a name which
can be, but does not have to be, the name of the calendar in the Google web
view.

To import calendar entries, the appropriate authentication process must be
completed with Google, and then you can select (Import) from the calendar
edit screen to initiate an import. Importing is also carried out as a
system "cron" task.

Calendar Events are made available via the normal Entity data patterns,
including as a source for Views.


REQUIREMENTS
------------

 * This module requires no Drupal modules outside of Drupal core.

 * The composer.json file defines modules required for use, notably the
   google/apiclient package that provides the Google_Client class. If you
   install this module using composer, these will be installed as well,
   otherwise you must install them yourself.


INSTALLATION
------------

 * Install the Google Calendar Import module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

 * You must generate and download the "credentials.json" file from the Google
   API developer pages before you can start importing calendar entries from
   Google (although you can set up the module without it). Visit the Settings
   page to upload this file.

CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the module.
2. Navigate to admin/google-calendar/calendar to add a Google calendar.
3. Enter the name of the calendar and the ID (This can be obtained from the
   "Integrate Calendar" section of your calendar's settings.)
4. Save.


MAINTAINERS
-----------

 * Drew Trafton (dtraft) - https://www.drupal.org/u/dtraft

