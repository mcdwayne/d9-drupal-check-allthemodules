TABLE OF CONTENTS
-----------------

* Introduction
* Requirements
* Installation
* Configuration
* Troubleshooting
* FAQ
* Maintainers

INTRODUCTION
------------
The DBLog Pager module is quite simple. It adds paging options (Next,
Previous) to the individual events view (/admin/reports/event/%ID%). Also
adds First and Last links (if desired you can turn them off). Overrides the
default display of a blank page if an event ID can't be found (configurable).

 * To submit bug reports and feature suggestions, or to track changes, see
 the [issue queue](https://www.drupal.org/project/issues/dblog_pager).

 * See also [DBLog Pager's page](https://www.drupal.org/project/dblog_pager).

REQUIREMENTS
------------
This module requires the core module [Database Logging]
(https://www.drupal.org/documentation/modules/dblog). It is not enabled by
default so you may have to enable it.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See [install
instructions](http://drupal.org/documentation/install/modules-themes/modules-8)
in the Drupal documentation for further information.

CONFIGURATION
-------------
Configuration can be accessed at /admin/config/development/logging/dblog_pager.

There are three configuration options:

  * *Show First/Last Links:* Checkbox to enable/disable visibility of the last/
    first event links. [Default: TRUE]
  * *Override Bad ID Processing:* Checkbox to enable/disable overriding DBLog's
    default of displaying a blank page if the log entry can't be found. If
    active, will display a warning. [Default: TRUE]
  * *Redirect To Last Log Record:* If *Override Bad ID Processing* and this is
    active then DBLog will display a warning and redirect to the most recent
    log record (that matches whatever filtering you may have active) instead
    of just a warning over a blank page. [Default:TRUE]

TROUBLESHOOTING
---------------
* If the paging functionality doesn't appear, try the following:

  * Clear your cache.

  * If it is just the Last/First links not showing make sure they are
  configured to appear.

  * Try uninstalling and reinstalling the module.

FAQ
---
Any questions? Ask away on the issue queue or email: design@briarmoon.ca.

MAINTAINERS
-----------

Current maintainers:
* Nick Wilde (NickWilde) - https://www.drupal.org/u/nickwilde

This project has been sponsored by:
* BriarMoon Design
   Full service web development and design studio. Specializing in responsive,
   secure, optimized Drupal sites. BriarMoon Design can help you with all your
   Drupal needs including installation, module creation or debugging, themeing,
   customization, and hosting. Visit http://design.briarmoon.ca for more info.
