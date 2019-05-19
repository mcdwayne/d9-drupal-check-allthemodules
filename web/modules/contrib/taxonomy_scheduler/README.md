CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------
Taxonomy Scheduler offers the possibility to add a "Publish on" field
on the level of a vocabulary, where a date and time can be filled in for
scheduled publishing per vocabulary term. A cron run will consequently 
change the status of the term from Unpublished to Published.

 * For a full description of the module visit:
  https://www.drupal.org/project/taxonomy_scheduler

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/taxonomy_scheduler


REQUIREMENTS
------------

* hook_event_dispatcher (https://www.drupal.org/project/hook_event_dispatcher)


RECOMMENDED MODULES
-------------------

* None.


INSTALLATION
------------

Install the Taxonomy Scheduler module like you would install any other
contributed module.


CONFIGURATION
--------------

  1. Configuration can be found at "/admin/config/taxonomy_scheduler".
  2. Choose the vocabularies where the field should be added.
  3. Optionally a field name can be chosen and whether the field is required.
  4. Save the settings.
  5. Make sure a regular cron is setup, so your taxonomy terms are published on the set time.

MAINTAINERS
-----------

The 8.x branches were created by:

 * Vincent Laurens van den Berg (vinlaurens) - https://www.drupal.org/u/vinlaurens

as an independent volunteer
