CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Using Views
 * Maintainers


INTRODUCTION
------------

The Entity Auto Term module provides the functionality that when a entity is
created, a taxonomy term is generated automatically using its title in any
associated vocabularies. 

 * For a full description of the module visit:
   https://www.drupal.org/project/eat

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/eat


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Entity Auto Term module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Entity Auto Term and select
       which entity to add entity auto term functionality. Currently, the module
       only works with node types.
    3. Check the Vocabularies "Tags" box to set vocabularies to entity types and
       bundles. Save configuration.
    4. A taxonomy term is generated automatically using its title in any
       associated vocabularies.

USING VIEWS
-----------

    1. Add contextual filter `Content: Has taxonomy term ID`
    2. Select `Provide default option` under `When the filter value is NOT available`
    3. Then select `Content ID from path for EAT` from the `Type` select box


MAINTAINERS
-----------

 * Alex Burrows (aburrows) - https://www.drupal.org/u/aburrows
 * Chandra Rajakumar (chandraraj) - https://www.drupal.org/u/chandraraj
