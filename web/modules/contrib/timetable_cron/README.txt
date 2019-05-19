
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers



INTRODUCTION
------------

Normally on a cron run, all cron tasks will be execute.
This is not always necessary, because some of your cron tasks
must i.e. only run once at day.
With this lightweight and simple module you can change
the time of execution of each cron task.
We recommend to run the main cron (URL) all 5min.

Our module swap the standard cron service by altering.
Thereforce it is possible that other modules (i.e. Elysia cron
or Ultimate cron) do not work together.
Use always only one cron module on your Drupal system.

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/sandbox/ucola/2654206



REQUIREMENTS
------------

At this time no requirements needed.



Recommended modules
-------------------

At this time no Recommended modules needed.


INSTALLATION
------------

 * Install the module



CONFIGURATION
-------------

 * Run the cron onced manualy configuration page > system > cron

 * Go to the configuration page > system > timetable cron

    - Now you can edit each cron and set there your own time

    - If you want to force a cron on next run, you can click on force link

    - Logic of Minute / Hour / Day / Month / Week day is adapted from unix cron
      I.e. Minute: */30, Hour: *, Day: *, Month: *, Week day: *
      That means this cron runs all 30minute

    - You can also copy a cron by clicking "save as new cron" on edit.
      So you can setup multiple times for a cron.



TROUBLESHOOTING
---------------

  * Is the cron list empty?

    - Run the cron manualy



MAINTAINERS
-----------

Current maintainers:
  * Ursin Cola (cola) https://www.drupal.org/u/cola
  * Chris Casutt (handkerchief) https://www.drupal.org/u/handkerchief

This project has been sponsored by:
 * soul.media
   Drupal agency, www.soul.media
