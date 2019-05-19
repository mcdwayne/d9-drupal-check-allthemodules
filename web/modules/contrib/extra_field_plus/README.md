CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Extra Field Settings Provider module provides an interface and the extra
field base plugins with editable display settings.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/extra_field_plus

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/extra_field_plus


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

 * Extra Field - https://www.drupal.org/project/extra_field


INSTALLATION
------------

 * Install the Extra Field Settings Provider module as you would normally
   install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

The module does not have a configuration page. You can find all extra field
plugins at Reports > Extra Fields Plugins List.


USES
----

To provide the extra field plugin with display settings you must at least
implement the ExtraFieldPlusDisplayInterface.

But there are two base plugins which can help you with this. Just extend
ExtraFieldPlusDisplayBase or ExtraFieldPlusDisplayFormattedBase plugins.

All yours extra field plugins have to be placed in
your_custom_module/src/Plugin/ExtraField/Display folder.

Example plugins with simple and formatted output are in extra_field_plus_example
module.


MAINTAINERS
-----------

 * Andrew Tsiupiakh (andrew_tspkh) - https://www.drupal.org/user/3302731

Supporting organizations:

 * Drupal Ukraine Community - https://www.drupal.org/drupal-ukraine-community
 * Smile - https://www.drupal.org/smile
