CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * How to use
 * Full synchronisation
 * Maintainers


INTRODUCTION
------------
Current maintainer: laszlo.kovacs <laszlo.kovacs@cheppers.com>

This module is for developers when on dev site one would like to export only
the modified config files shown in
Configuration > Configuratiom management > Synchronize
(/admin/config/development/configuration) tab.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/config_partial_export

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/config_partial_export?categories=All


REQUIREMENTS
------------
No special requirements.


INSTALLATION
------------
Before you install the module it's advised to do a full synch of your
configuration otherwise all your config files will be listed by this module.
For more details see Full synchronisation part of this document.

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
The module has no menu or modifiable settings. There is no configuration. When
enabled a 'Partial config' tab will appear on
admin/config/development/configuration page.

HOW TO USE
----------------
After enabling a 'Partial config' tab will appear on
admin/config/development/configuration page. Click on the Partial Export tab
and select the files you want to export to a tarball. In addition you can add
to the list the system.site.yml file which holds the UUID of your site.
After export you can copy the files from the tarball to your config/install
folder to apply those changes on next install.

FULL SYNCHRONISATION
--------------------
Steps to do a full synch:
 * Do a full export then an import. Your config files will be copied then to the
   staging directory (by default: sites/default/files/config_some-hash/staging).
 * Do a small modification in your settings. The files related with this
   modification will be listed on admin/config/development/configuration page.
 * Click "Import all" button. Be aware, all modifications you have done since
   last import will be lost!

MAINTAINERS
-----------
Current maintainers:
 * László Kovács - https://www.drupal.org/u/laszlo.kovacs

This project has been sponsored by:
 * Cheppers Ltd.
   A team of experienced web developers working with the Drupal content
   management system. Expertised in Drupal consulting, development, professional
   website design, search engine optimization (SEO), e-commerce and data
   migration.
   Visit https://cheppers.com/ for more information.
