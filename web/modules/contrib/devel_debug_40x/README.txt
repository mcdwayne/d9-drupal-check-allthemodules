CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Devel Debug 40x module registers an event subscriber that will dump 403 and
404 exceptions through Devel's dumper manager to help you debug unexpected 403
or 404 errors.

This module is a development module and should not be used in production.

For a full description of the module, visit the project page:
https://www.drupal.org/devel_debug_40x

To submit bug reports and feature suggestions, or to track changes:
https://www.drupal.org/node/add/project-issue/devel_debug_40x


REQUIREMENTS
------------

This module depends on the contributed devel module.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * After enabling the module, 403 or 404 exceptions will be dumped using Devel's
   dumper manager (i.e. equal to dpm()). Refer to the documentation provided by
   the Devel module for more information about configuration options.


MAINTAINERS
-----------

Current maintainers:
 * Patrick Fey (feyp) - https://drupal.org/u/feyp

This project has been partly sponsored by:
 * werk21 GmbH
   werk21 is a full service agency from Berlin, Germany, for politics,
   government, organizations and NGOs. Together with its customers,
   werk21 has realized over 60 Drupal web sites (since version 5).
   Visit https://www.werk21.de for more information.
