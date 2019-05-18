
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developing for PDB
 * Maintainers


INTRODUCTION
------------

Welcome to Decoupled Blocks. This module is a javascript-framework-agnostic,
progressive decoupling tool to allow custom blocks to be written by front end
developers in the javascript framework of their choice, without needing to know
any Drupal API's.

It keeps individual components encapsulated in their own directories containing
all the css, js, and template assets necessary for them to work, and using an
info.yml file to declare these components and their framework dependencies to
Drupal.

 * For a full description of this module, visit the project page:
   https://www.drupal.org/sandbox/mrjmd/2664138


REQUIREMENTS
------------

There are no special requirements at this time. In the future, we may abstract
some items to a component_api project.


INSTALLATION
------------

 * Enable the module and at least one javascript framework sub-module (Angular 2
   is the most fleshed out currently).
 * For Angular 2 use:
     - Before enabling the Angular 2 sub-module you must navigate to the pdb_ng2
       directory and run `npm install`.
     - Before turning on production module in the module configuration form,
       make sure to run `tsc` from the pdb_ng2 directory to compile your .ts
       files into .js.


CONFIGURATION
-------------

 * Navigate to a block placement UI (core block UI or Panels, for example).

 * Add a block, and select one of the PDB example components.

 * Save your page and the decoupled component will appear!


DEVELOPING FOR PDB
------------------

 * Writing your own components:

   - Writing components for existing frameworks should be straightforward, write
     them as usual and add an info.yml file declaring them to the module. Some
     framework implementations may provide extra tooling or require additional
     boilerplate to use full functionality, see their individual README.txt
     files for more information.

   - When you find gaps in functionality you need, post them to the github issue
     queue, as this module is still in early development stages.

 * Writing your own framework definitions:

   - By extending the main pdb block and deriver classes and adding a library
     file, you can craft your own js framework definition modules! We would love
     to see support for other frameworks.

   - For now you can use the existing pdb_ng2 and pdb_react as examples for
     how to proceed.

 * Options for getting data from Drupal in components

   - If you give your component a required context, it will only appear on pages
     with that context available, and automatically pass that data forward using
     drupalSettings.pdb.contexts. See the ng2_example_node component for sample
     code.

   - If your component requires instance configuration you can add fields in the
     info.yml file that will be displayed on block placement for the site
     builder to fill out. Configuration values are passed to the client via
     drupalSettings.pdb.

   - Use a JSON endpoint.

   - Use the GraphQL module.


MAINTAINERS
-----------

Author:
 * Matt Davis (mrjmd) - https://www.drupal.org/u/mrjmd
    <matt.davis at yourdrupaldev DOT com>

Current maintainers:
 * Matt Davis (mrjmd) - https://www.drupal.org/u/mrjmd
   <matt.davis at yourdrupaldev DOT com>

Early Contributors:
 * Jason Smith
 * Jeff Lu
 * Shawn Stedman
 * Emma Cortes
 * Diego Barahona
 * Mark Casias
 * Bob Kepford
 * Derek Reese
 * Adam Globus-Hoenich
 * Ted Bowman

This project has been sponsored by:
 * Mediacurrent
   Mediacurrent helps organizations build highly impactful, elegantly designed
   Drupal websites that achieve the strategic results they need. Visit
   http://www.mediacurrent.com for more information.

 * Acquia
   Commercially Supported Drupal. Visit http://acquia.com for more information.
