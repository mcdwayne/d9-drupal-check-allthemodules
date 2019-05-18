CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Persistent Log module records specific kinds of log events in permanent
storage, which is not truncated like the core watchdog log. It is particularly
useful for capturing rare events in sites that generate a high volume of log
messages, which would otherwise get lost quickly.

 * For a full description of the module visit:
   https://www.drupal.org/project/dblog_persistent

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/dblog_persistent


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Persistent Log module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Reports > Persistent log > and add a
       persistent log channel.
    3. Choose which events to capture from the Types dropdown. Additionally,
       there is an option to add more fields using a comma-separated list.
    4. The channel can filter messages by type, severity, and/or message text.
       Save.

Each channel can be viewed and cleared individually.


MAINTAINERS
-----------

 * Christoph Burschka (cburschka) - https://www.drupal.org/u/cburschka

Supporting organization:

 * PwC's Experience Center - https://www.drupal.org/pwcs-experience-center
