CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The szentiras.hu Reference Formatter module provide a custom field formatter 
for Bible references (simple plain text field) to szentiras.hu. The module is 
mainly for Hungarian Drupal users, since szentiras.hu provides Hungarian 
Bible translations only. Currently supports 3 behaviours:

* Simple link: create a link to szentiras.hu.
* Load text on click: on clicking loads the referenced text from szentiras.hu 
  by its API.
* Auto load text: load all referenced text on page onload event from 
  szentiras.hu by its API.


REQUIREMENTS
------------

This module requires only core Drupal modules.


INSTALLATION
------------
 
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/project/szentirashu_formatter for further information. There is no
permission for this module.


CONFIGURATION
-------------

* Create a Content Type with a custom plain text field. You can set unlimited 
  number of values. Add some Bible reference to the node for example: 
  "Ter 1,1-10."
* Under Manage Display set field format to "Szentiras.hu formatter". 
* After that you can select here preferred Translation and the preferred 
  behaviour too.


MAINTAINERS
-----------

Current maintainers:
 * Gellért Gyuris (bubu) - https://www.drupal.org/user/16787
