# Autocomplete Field Match

INTRODUCTION
------------
A module for those who need to allow free text in autocomplete fields that 
matches to a field on either the same entity or a field within a referenced
entity. Field types available are Entity Reference, Field collection, or
Paragraphs, though it may work on other referenced entities.
File an issue in the queue if needed. Note that this module currently
only matches to a referenced entity one level deep. 
(i.e. it will not match a field on a referenced entity of a referenced entity.)

REQUIREMENTS
------------

This module requires the following modules:
 * Latest release of Drupal 8.x.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------
In the manage form display select Autocomplete Field Match on the field. 
Open the gera on the right to select the fields to match and how to match them.
Update the widget using the update button when you have made your selections.
Save the form display.

TROUBLESHOOTING
---------------
This widget should just work if configuration steps are followed.
If it does not please file an issue in the queue. The module will
fallback to standard autocomplete if an entity is selected from the dropdown.

FAQ
---
None yet.

MAINTAINERS
-----------
 * (el1_1el) - https://drupal.org/user/1795292

This project has been sponsored by:
 * University of Michigan Library
