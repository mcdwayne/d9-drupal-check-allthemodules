CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Kayak Integration module add a block that uses Kayak's official widget. 
It provides an interface where user can start searching hotels or flights.

Now you can add a block with only flights searcher or a block with only hotels
searcher.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/kayak_integration

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/kayak_integration

REQUIREMENTS
------------

This module no require additional modules.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

You must configure the blocks to work correctly in
/admin/structure/block/manage.
There is no configuration. You need to place this block in a region.
The configuration has 3 custom fields:
* Kayak Language: It allows user to customize Kayak's Widget language.
                  It requires a two letters code of language.
* Kayak Country Code: It allows user to customize Kayak's Widget country
                      code. It requires a two letters code of country code.
* Kayak Currency: It allows user to customize Kayak's Widget money code
                  (currency). It requires a three letters code of currency.

MAINTAINERS
-----------

Current maintainers:
 * Manuel Borrego (maboresev) - https://www.drupal.org/u/maboresev

This project has been sponsored by:
 * SDOS
 We create, develop and implement technology that feels.
