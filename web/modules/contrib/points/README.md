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

The Points module defines a Point entity type. Site builders can add fields to
reference this type of entity in order to implement financial or transnational
type of applications.

This module tries to be the successor of Userpoints Module for Drupal 7.
Contrary to the D7 version, in D8, Points can be attached to any entity type.


 * For a full description of the module visit:
   https://www.drupal.org/project/points

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/points


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Points module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content Type > [content type to
       edit] Manage fields.
    3. Add a new field of Reference type "Other", give it a label, save and
       continue.
    4. In the Reference Type dropdown select Point, set allowed values, and save
       field settings.
    5. In the Reference Type field select the Reference method, sort by
       value, and sort direction. Save settings.

Update Points via Web Services or API:
Points Module implements a state tracking mechanism to prevent a same Point
Entity being updated by multiple threads/clients who are not aware of other
potential updates. To use API or web services to update Point, you will need to
set the state of the Point Entity instance to the exact number of Point at the
time retrieved.


MAINTAINERS
-----------

 * Jingsheng Wang (skyredwang) - https://www.drupal.org/u/skyredwang

Supporting organization:

 * INsReady - https://www.drupal.org/insready
