Paragraph Force Remove


CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

This module is an extension of the Paragraphs module. It creates
an admin page where every revision of paragraphs can be viewed.
It then has a button interface that allows for the deletion of all
paragraph revision of a certain paragraph type. This is useful when
you want a paragraph type to be deleted, but it is tied to an entity
by an old revision. 


REQUIREMENTS
------------

This module requires the following modules:

  * Paragraphs (https://www.drupal.org/project/paragraphs)



INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal 
 module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-7
   for further information.


CONFIGURATION
-------------
 
 * There is no current configuration.


TROUBLESHOOTING
---------------

 * If you get a error status when trying to delete all data of a
 certain paragraph type, make sure that the paragraph is not 
 currently being used in an entity.

 * This module tries to cover all areas so that paragraph types
 can be deleted. If you come across an instance where this module
 does not help, please leave an issue report here:
 https://www.drupal.org/project/paragraph_force_remove


FAQ
---

Q: Does this module help with garbage collection?

A: Yes. This module's goal is to remove data tied to paragraph
types that are no longer needed or wanted. When deleting a paragraph
type, if you come across some database garbage not removed. Please, 
leave an issue report here:
https://www.drupal.org/project/paragraph_force_remove


MAINTAINERS
-----------

Current maintainers:
 * Bobby Saul - https://www.drupal.org/u/bobbysaul
