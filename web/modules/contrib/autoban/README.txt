INTRODUCTION
------------

This module allows you to automatize IP ban by cron using module rules.

You create rule which finds IP in watchlog table entries and then module
inserts IP to table for banned IP. You must enable at least one IP Ban Provider
by enabling submodules. Now this is a Autoban Core Ban and Autoban Advanced Ban
(https://www.drupal.org/project/advban). To enable these modules you must enable
or install IP ban modules (Ban, Advanced Ban).

Rules for ban IP consist of:
- Type (watchdog type, like "page not found").
- Message pattern (rules seek in watchdog message as "LIKE %message_pattern%").
- URL referrer pattern.
- The threshold number of log entries.
- IP ban provider


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   Visit: https://www.drupal.org/node/1897420 for further information.

 * Enable Autoban Core Ban Provider and/or Autoban Advanced Ban Provider
   submodules


CONFIGURATION
-------------

* Configure at: [Your Site]/admin/config/people/autoban
  or: Administration > Configuration > People > Autoban

* In order to use this module you need the "Administer autoban"
  permission.

* Analyze watchdog table (/admin/reports/dblog).

* Go to the autoban admin page (/admin/config/people/autoban). Create and
  test rules or ban IP addresses for current rule.

* Cron will be ban IP using autoban rules.


TROUBLESHOOTING
---------------

* A rule's type and message pattern looks in watchlog table. You need put non
  translated value.

* The module using cron for automatic IP ban. If cron is disabled, you can
  click "Ban all" button at Show Ban IP for all rules tab.


MAINTAINERS
-----------

Current maintainers:
 * Sergey Loginov (goodboy) - https://drupal.org/user/222910
