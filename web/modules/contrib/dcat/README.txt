CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The DCAT module provides the possibility to integrate external DCAT providers
and import the datasets that those DCAT feeds offer. All imported data can be
combined and exported at a specific endpoint using the dcat_export module

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/dcat

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/dcat


REQUIREMENTS
------------

This module requires the following modules:

Inline entity form is used so a dataset can be created from a single form,
including the distributions, agent, vcards.

 * Inline Entity Form - https://www.drupal.org/project/inline_entity_form

For the dcat_import submodule the Migrate (part of core), Migrate plus and
Migrate tools modules are used to import the DCAT feeds.

 * Migrate Plus - https://www.drupal.org/project/migrate_plus
 * Migrate Tools - https://www.drupal.org/project/migrate_tools


INSTALLATION
------------

Install the DCAT module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to /admin/modules and enable the module and its
       dependencies.
    2. Navigate to /admin/structure/dcat to administer DCAT

The main module provides the following entity types:
 * Dataset
 * Distribution
 * Agent
 * vCard

All these entities have the necessary fields to conform with DCAT-AP.

DCAT export:
    1. Navigate to /admin/modules and enable the dcat_export module.
    2. Navigate to /admin/structure/dcat/settings/dcat_export and fill in all
       configuration fields.
    3. Navigate to /dcat to get a DCAT export. By setting the _format query
       parameter the output format can be chosen.
       Note: Format selection is not done by Accept header due to
       https://www.drupal.org/node/2501221.


MAINTAINERS
-----------

 * Wesley De Vrient (wesleydv) - https://www.drupal.org/u/wesleydv
 * Lennart Van Vaerenbergh (lennartvv) - https://www.drupal.org/u/lennartvv

Supporting organization:

 * Digipolis Gent - https://www.drupal.org/digipolis-gent
