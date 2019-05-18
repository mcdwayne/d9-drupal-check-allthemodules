CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 
INTRODUCTION
------------
 
The Klipfolio Field module provides a fieldtype in which Klipfolio widgets can 
be embedded.
Display options for the widget can be set in the fields display settings.
 
  * For a full description of the module, visit the project page:
    https://www.drupal.org/project/klipfolio_field
 
  * To submit bug reports and feature suggestions, or to track changes:
    https://www.drupal.org/project/issues/klipfolio_field


REQUIREMENTS
------------
 
This module has no dependencies on other modules.
 
 
INSTALLATION
------------
  
* Install as you would normally install a contributed Drupal module. Visit:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

CONFIGURATION
-------------
 
* Add a Klipfolio field to an entity (node, paragraph, etc...)
* Configure the display options for the Klipfolio field (width & theme).
* In Klipfolio select the Klip to embed. 
  See https://support.klipfolio.com/hc/en-us/articles/215548548-Embedding-a-Klip-into-an-HTML-page
* From the generated emebd code, the KLIP's ID can be extracted. Look for a 
  code like: '588cf1e6f14d22adca9815fddfbf6c1e'.
* To render a Klip in a Drupal field, fill in the KLIP's ID in the Klipfolio 
  field;
* Option: add a title for the Klip.


MAINTAINERS
-----------

Current maintainers:
 * K3vin_nl - https://www.drupal.org/u/k3vin_nl
