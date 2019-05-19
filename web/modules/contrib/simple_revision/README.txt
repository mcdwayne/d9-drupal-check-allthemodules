CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Configuration
 * Other IRC Hooks
 * Design Decisions


INTRODUCTION
------------

Simple Taxonomy Revision module enables revisions for taxonomy terms for Drupal 8.

As Taxonomy Revision in Drupal 7 not ported to Drupal 8, and Drupal 8 Core does not provide revisioning for taxonomy terms, enabling this module provides an option to create revision for any term.

A new tab Simple Revisions in taxonomy term configuration from where we can revert and delete revisions

INSTALLATION
------------

Download Module and place folder simple_revision in modules folder and Enable it.

CONFIGURATION
----------------

•	After Enabling module, go to "Simple Taxonomy Revision settings" in Configuration admin menu.
•	If you want already created Taxonomy terms to be in revisions list, click "Initialize all Taxonomy terms".
•	Go to any term already created, or create any term, 'Simple Revisions' tab appears, you can revert to any term.
•	Revisions will be created whenever term will be updated or created.

UPCOMING FEATURES
----------------

•	Revision log message for revision.
•	Integrating Diff module for check changes.
•	Views Integration.