CONTENTS OF THIS FILE
----------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

Content notify gives content editors the ability
to schedule the notification of nodes so that
content editors have been notified of content.

Module have two notification process.

1. Notification of Unpublish content

Content editor can notified of content which will be unpublished
in certain time later. You can configure in admin panel how many days before
you want to notify before content is going to unpublished. This part of
module depends on Scheduler module so to achieve full features of module
it is required to have scheduler module.

2. Notification of invalid/Old content

Content editor can notified of content which are very old in the system.
You can configure in admin panel what should be content age to consider as
old or invalid content. You will notified so you can take care of old
content in the system.


REQUIREMENTS
------------

 * Content notify uses the following Drupal 8 Core components:
     Datetime, Field, Node, Text, System.

 * There are no special requirements outside core.


REQUIRED MODULES
-------------------

 * scheduler (https://www.drupal.org/project/scheduler):

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. See:
     https://drupal.org/documentation/install/modules-themes/modules-8
     for further information.


CONFIGURATION
-------------

 * Configure user permissions via
     Administration » People » Permissions
     URL: /admin/people/permissions#module-content_notify


   - Content notification of nodes

     Users with this permission can enter dates and times for unpublish,
     invalid notification settings,
     when editing nodes of types which are content notify-checked.

   - Administer content notification

     This permission allows the user to alter all content notify configuration
     settings. It should therefore only be given to trusted admin roles.

 * Configure the Content notify global options via
     Administration » Configuration » Content Authoring
     URL: /admin/config/content/content-notify

   - Basic settings for choosing
      content types,
      mail settings,
      duration of notification.

   - Email will go as digest email per user depends of content in cron run.


TROUBLESHOOTING
---------------

 * To submit bug reports and feature suggestions, or to track changes see:
     https://drupal.org/project/issues/content_notify

 * To get help with crontab jobs, see https://drupal.org/cron


MAINTAINERS
-----------

Current maintainers:
Takim Islam -           https://www.drupal.org/u/takim
