CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Toggle Editable Fields module is a formatter to transform "classic" boolean
field formatter to toggle editable field directly on 'view' display or on views
lists.

 * For a full description of the module visit:
   https://www.drupal.org/project/toggle_editable_fields

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/toggle_editable_fields


REQUIREMENTS
------------

This module requires the following library:

 * Bootstrap Toggle Plugin - https://github.com/minhur/bootstrap-toggle/


INSTALLATION
------------

Install the Toggle Editable Fields module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Download Bootstrap Toggle Plugin and place it in the libraries folder
       (named `bootstrap-toggle`) or use bower if there is a Composer workflow.
    2. Navigate to Administration > Extend and enable the module.
    3. Create a boolean field.
    4. On display choose `Toggle Editable Formatter` as formatter and configure
       the settings.
    5. The user can now switch the state of the field in every place
       (view/entity view etc...).


MAINTAINERS
-----------

 * Alexandre Mallet (woprrr) - https://www.drupal.org/u/woprrr

Supporting organization:

 * NeoLynk - https://www.drupal.org/neolynk