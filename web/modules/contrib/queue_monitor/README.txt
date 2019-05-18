CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Queue Monitor module monitors the status of Drupal queue. It will be
executed immediately if a Drupal queue exists, otherwise, it will be in waiting
state.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/queue_monitor

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/queue_monitor


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Queue Monitor module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to /admin/config/development/queue_monitor for basic settings.

Listening to the specified queue:
```
$ drush queue_monitor:run myqueue
```

Listening all queues:
```
$ drush queue_monitor:runall
```


MAINTAINERS
-----------

Supporting organization:

 * DAVYIN Internet Solutions - http://www.davyin.com
