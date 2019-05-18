CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This is the module that provide functionality of sorting
queue workers definitions. That causes an effect on queue execution order
during cron run. Get the additional advantage of Queue API of Drupal core.
This tiny module allows control execution order of defined queue workers
by the Cron handler.

 * For a full description of the module visit:
   https://www.drupal.org/project/queue_order

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/queue_order


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.
Supported version of the Drupal core.

UI --This module provide functionality without any admin UI. It should be
useful on production. Use Queue Order UI
(https://www.drupal.org/project/queue_order_ui) for development process.


INSTALLATION
------------

 * Install the Queue order module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

All weight values of queue workers stored in order property
of queue_order.settings config object. It contains key - value array,
where key is the id of queue worker, value - the weight value.
  
```yaml
# Example of queue_order.settings config object:
order:
  queue_worker_1: 0
  queue_worker_2: -1
  queue_worker_3: 1
  queue_worker_4: 2
```


MAINTAINERS
-----------

 * Oleh Vehera (voleger) - https://www.drupal.org/u/voleger

Supporting organization:

 * GOLEMS GABB - https://www.drupal.org/golems-gabb

GOLEMS GABB is a team of experienced developers!
Our work includes strategy, design, and development
across a variety of industries.
