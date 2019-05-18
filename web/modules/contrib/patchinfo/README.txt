CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Patch Info module allows you to track information about patches in modules
or themes. It will show the information prominently in the update report and on
the update manager form.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/patchinfo

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/patchinfo


REQUIREMENTS
------------

This module does not depend on any other modules.


RECOMMENDED MODULES
-------------------

 * Drush (https://github.com/drush-ops/drush):
   When enabled, you can use drush patchinfo-list to get a list of
   patches applied to Drupal core or contributed modules on your system.
   Refer to drush help patchinfo-list for a list of available options.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Choose one or multiple patch information sources.

   In 7.x and 8.x-1.x, all patches were tracked by adding information to
   *.info.yml files. Since 8.x-2.x, PatchInfo supports patch source plugins,
   that will be used to get patch information for your themes and modules.

   Two patch sources ship as submodules of PatchInfo. The 'info.yml' patch
   source gets patch information from '*.info.yml' files and the 'composer.json'
   patch source gets patch information from 'composer.json' files. See below
   for more information on how these patch sources work.

   You can use any number of patch sources at the same time. Just enable the
   submodule(s) that provide the patch source(s) you want to use or write a
   custom module that provides your own patch source plugin.

   The 'info.yml' patch source is fully compatible with patch information added
   using PatchInfo 8.x-1.x.

 * Add information about a patch using info.yml patch source

   In the *.info.yml file of a patched theme or module, add a new list like the
   one shown below:

   patches:
     - 'https://www.drupal.org/node/1739718 Issue 1739718, Patch #32'

   You can add multiple entries to the list. Each entry should start with the
   URL of the issue or patch followed by any kind of information about the
   patch. The URL is optional.

   You can use any URL or description, that is convenient to you.

   If you are patching a submodule, you may add the patch entry to the
   *.info.yml file of the submodule.

 * Add information about a patch using composer.json patch source

   The composer.json patch source assumes, that 'cweagans/composer-patches' is
   used for patch management. See https://github.com/cweagans/composer-patches
   for more information.

   For Drupal Core, it will check for a composer.json in your Drupal root
   directory or in the 'core' folder.

   Presently, the source plugin will skip any patches for packages outside the
   'drupal/' namespace.

 * Exclude module from update check

   The module will extend the update report settings form at Reports »
   Available Updates » Settings with a textarea, where you can list
   modules, that should be excluded from the update check. List one
   module per line. Use the machine readable module name of the module.
   Modules, which are excluded from the update check will be displayed
   prominently above the update report.

 * Write your own patch source plugin

   If you want to write your own custom patch source plugin, look at the
   existing submodules that provide patch source plugins for good examples.


MAINTAINERS
-----------

Current maintainers:
 * Patrick Fey (feyp) - https://drupal.org/u/feyp
 * David Franke (mirroar) - https://drupal.org/u/mirroar

This project has been partly sponsored by:
 * werk21 GmbH
   werk21 is a full service agency from Berlin, Germany, for politics,
   government, organizations and NGOs. Together with its customers,
   werk21 has realized over 60 Drupal web sites (since version 5).
   Visit https://www.werk21.de for more information.
