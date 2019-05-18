#Cleaner

##Introduction
The Cleaner module allows the admin to set a schedule for clearing
caches, watchdog, and old sessions.

There are function in Drupal which will cause "expired" entries
in some cache tables to be deleted.

There are still times and/or cache tables that don't get cleared
in any of those scenarios. Many sites will not be impacted by this,
but a few will (just search on drupal.org and you will see many posts
from people having problems).

##Installation
Standard module installation applies.

##Settings
The module will do nothing until you enable its clearing functions
(for your own protection).

The module settings can be found at
Admin >> Configuration >> System >> Cleaner.

1. Select the frequency (interval) at which it will run.
   Note that the Cron settings may change the actual frequency of execution.
2. Select which clearing functions you desire.

##Logging
This module will log its actions.

##Extending
Since the module uses EventDispatcher flow - developers are able to write their own
event subscriptions in order to extend/alter the module's functionality.
