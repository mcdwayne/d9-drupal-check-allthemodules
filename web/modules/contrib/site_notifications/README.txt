CONTENTS OF THIS FILE
--------------------

* Introduction
* Requirements
* Installation
* Configuration
* Troubleshooting
* FAQ
* Maintainers


INTRODUCTION
------------

Site Notifications module provides a facility to display content based
notifications to the users with selected user roles and for selected content
types.

It provides a block which contains configurable number of notifications based on
settings.

It also refreshes the notifications asynchronously depending on configurable
refresh interval.

It provides configurable settings of user roles which decides whether to
show Notification Block to them.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/site_notifications

* To submit bug reports and feature suggestions, or to track changes:
  https://www.drupal.org/project/issues/site_notifications


REQUIREMENTS
------------

This module requires no module outside of Drupal core.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/node/1897420
  for further information.


CONFIGURATION
-------------

1. Enable Site Notifications Module (admin/modules).
2. Enable Site Notifications Block and place in region you want (admin/structure
   /block).
3. Go to Site Notifications Settings (admin/config/site_notifications/settings)
   and
   - Check the Content Types for which you want notifications.
   - Check User Roles to whome the notifications to be displayed.
   - Enter Refresh Interval (in ms) for asynchronously refresh notification
     block content.
   - Select Number of Notifications to be shown on block.
4. Check the checkbox "Check if you want to enable notifications" to enable and
   display notification block.
5. Submit Settings Form.


TROUBLESHOOTING
---------------

* If the Notification Block does not display, check the following:

   - Is the "Check if you want to enable notifications" checkbox is checked
   in settings page?

   - Is appropreate role is checked in settings page?

   - Is Site Notification Block is placed in any intended region?


MAINTAINERS
-----------

* Hemant Joshi (hemant.joshi) - https://www.drupal.org/u/hemantjoshi-0
