CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Workflow Moderation is a module for the configuration of workflow to create, moderate and publish content revisions.

 * Workflow Moderation handle the status of node depends on the states.
 * It override node status of Drupal core's "unpublished" and "published" with respective to workflow state configuration.
 * It affects the behavior of node revisions when nodes are published.
 * Moderation states are tracked per-revision; rather than moderating nodes, Workflow Moderation moderates revisions.

 - You use it in scenario's like this:

 * Authors write content that must be reviewed (and possibly edited) by moderators.
 * Once the moderators have published the content, authors should be prevented from modifying it while “live”, but they should be able to submit new revisions to their moderators.
 
 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/worklfow_moderation


REQUIREMENTS
------------

 * Workflow
 * Workflow State Configuration - We configure the state as "Published" and "Default Revision".


INSTALLATION
------------

Install the Drush Config Import Log module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

 * The configurations are simple, which is easy when you are familiar to the workflow configurations.
 * If the moderation is enabled then you can find a tab 'Latest revisions' on node menu tab.


MAINTAINERS
-----------

 * Punam Shelke - https://www.drupal.org/u/punamshelke

Supporting organization:

 * UniMity Solutions Pvt Limited -
   https://www.drupal.org/unimity-solutions-pvt-limited