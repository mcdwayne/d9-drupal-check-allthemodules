CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Taxonomy access fix module does what native Taxonomy lacks: more specific
Taxonomy permissions (and checking them correctly).

 * Adds 1 or 2 permissions per vocabulary: "add terms in X" (also "reorder terms
   in X" for D8)
 * Changes the way vocabulary specific permissions are handled
 * Changes the Taxonomy admin pages' access checks
 * Alters the vocabularies overview table to show only what you have access to
   edit or delete.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/taxonomy_access_fix

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/taxonomy_access_fix


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Taxonomy Access Fix module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

In order to access the admin/structure/taxonomy page, you must first set
permissions for the desired vocabularies.

A module can't add permissions to another module, so the extra "add terms
in X" permissions are located under "Taxonomy access fix" and not under
"Taxonomy".


MAINTAINERS
-----------

 * Oleksandr Dekhteruk (pifagor) - https://www.drupal.org/u/pifagor
 * rudiedirkx - https://www.drupal.org/u/rudiedirkx

Supporting organizations:

 * GOLEMS GABB - https://www.drupal.org/golems-gabb
