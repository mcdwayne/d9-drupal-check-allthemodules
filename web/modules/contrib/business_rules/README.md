CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Known Issues
 * Maintainers


INTRODUCTION
------------

The Business Rules module is inspired on Rules module and allow site
administrators to define conditionally actions execution based on events.
It's based on variables and completely build for Drupal 8.

This module has a fully featured user interface to completely allow the site
administrator to understand and create the site business rules.

It's also possible to extend it by creating new ReactsOn Events, Variables,
Actions and Conditions via plugins.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/business_rules
   or
   https://www.drupal.org/docs/8/modules/business-rules

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/business_rules


FEATURES
--------

 * Conditional event driven action execution
 * If, then, else conditions
 * Views result variable
 * Loop action execution
 * Custom form validations
 * Custom form changes to make fields required, optional, read-only and hidden
 * Dependent fields
 * Explicitly variables declaration
 * Flowchat diagram to each business rule
 * Extendable via plugins
 * Full debug rule execution block
 * Token integration
 * Safe mode - thanks fatmarker for the suggestion
 * Scheduled Actions
 * Change between Form view mode / Form display


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * dBug for Drupal - https://www.drupal.org/project/dbug

External library:
 * Download and copy the mxGraph version for Business Rules module from
   https://github.com/yuriseki/business_rules_js to
   \libraries\business_rules_js folder.


INSTALLATION
------------

 * Install the Business Rules module as you would normally install a contributed
 Drupal module. Visit https://www.drupal.org/node/1897420 for further
 information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Clear caches.
    3. Navigate to Administration > Configuration > Workflow > Business rules to
       manage business rules.
    4. To create a new action, access the "Actions" tab at the Business Rules
       page and select "Add Action" button. For extensive documentation visit:
       https://www.drupal.org/node/2869626.
    5. To create a new condition, access the "Conditions" tab at the Business
       Rules page and select "Add Condition" button. For extensive documentation
       visit: https://www.drupal.org/node/2869845.
    6. To create a new variable, access the "Variables" tab at the Business
       Rules page and select "Add Variable" button. For extensive documentation
       visit: https://www.drupal.org/node/2869849.
    7. After you've created all actions, conditions, and variables for the
       business rule, it's time to put all together by creating a new rule.
       Access the "Rules" tab at the Business Rules page and select "Add Rule"
       button. For extensive documentation visit:
       https://www.drupal.org/node/2869861.


KNOWN ISSUES
------------

 * There are some occasions that the subscribed events will not be available. It
   happens because the getSubscribed Events in some occasions is called before
   Drupal has prepared the container. I.e.: When the user adds new language. If
   it happens, just clear your cache.

 * The reactsOn event for "Entity is viewed" is triggered only if Drupal is
   loading the entity from database but not from cache. If you need to trigger
   this type of rules every time entity is being viewed, you need to disable
   caches for entities.

* The reactsOn event for "Entity is viewed" is triggered only if Drupal is
loading the entity from database but not from cache. If you need to trigger this
type of rules every time entity is being viewed, you need to disable caches for
entities.

MAINTAINERS
-----------
* Yuri Seki - https://www.drupal.org/u/yuriseki 
  yuriseki@gmail.com
