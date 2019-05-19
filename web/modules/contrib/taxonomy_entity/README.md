CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Taxonomy Entity module increases the range of functionality and control
over taxonomy vocabularies and terms.

This first release incorporates a set of changes that will make the hierarchy
functionality an exposed setting in the vocabulary configuration. This will
force the taxonomy entity forms to only allow relationships when the correct
option is chosen.


 * For a full description of the module visit:
   https://www.drupal.org/project/taxonomy_entity

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/taxonomy_entity


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Taxonomy Entity module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Taxonomy > [Vocabulary to Edit]
       to choose a Hierarchy.

No hierarchy:
 * This will make sure you can only sort your terms in the overview
   vertically, and not horizontally to add a relationship.
 * The relationship field in the taxonomy add/edit screens are also removed.

Single parent hierarchy:
 * This will make sure you can only add one parent in the relationship
   field in the taxonomy term add/edit forms. Which will prevent the often made
   mistakes that multiple parents are selected accidentally.
 * In this situation you can move terms horizontally in the terms overview to
   determine the parent relationships.

Multiple parent hierarchy:
 * Use case as we know it, multiple parent selection but without horizontal
   ordering of taxonomy terms in the terms overview.

MAINTAINERS
-----------

Supporting organization:

 * Synetic - https://www.drupal.org/synetic
