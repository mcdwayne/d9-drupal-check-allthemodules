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

The Flexible Layout module provides a dynamic regions for layout discovery that
can be output in rows and columns. Ideal for those using Bootstrap, as allows
wrappers/container/row/column setup, but can be used with any setup.

Bootstrap support can be enabled via your theme's css or CDN.


 * For a full description of the module visit:
   https://www.drupal.org/project/flexible_layout

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/flexible_layout


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Flexible Layout module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

For compatibility with field_layout an additional patch is required, which
can be found at https://www.drupal.org/project/drupal/issues/2924112.

For compatibility with Panels/Panelizer an additional patch is also required,
which can be found at https://www.drupal.org/project/panels/issues/2868828.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the Flexible Layout
       module.
    2. Flexible Layout will now become available in layout-compatible modules
       (Field Layout, Display Suite, Panels, etc.).
    3. Optionally, enable/disable Bootstrap support or change the source for
       Bootstrap grid CSS source at Administration > Config > Content Authoring
       > Flexible Layout.


MAINTAINERS
-----------

 * b_sharpe - https://www.drupal.org/u/b_sharpe
