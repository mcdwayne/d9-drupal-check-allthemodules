CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Node Revision Delete module lets you to track and prune old revisions of
content types.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_revision_delete

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/search/node_revision_delete


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Drush Help (https://www.drupal.org/project/drush_help):
   Improves the module help page showing information about the module drush
   commands.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.


CONFIGURATION
-------------

 * Configure the module in Administration » Configuration »
   Content authoring » Node Revision Delete:

   - You can set how many revisions do you want to delete per cron run and
     how often should revision be deleted while cron runs. You you can save
     the configurations and optionally start a batch job to delete old revisions
     for tracked content types. For this you need the
     'Administer Node Revision Delete' permission.

 * Configure each content type in Administration » Structure » Content types »
   Content type name:

   - Under the Publishing options tab, mark "Limit the amount of revisions for
     this content type" and set the maximum number of revisions to keep.

 * Drush commands

   - nrd-delete-cron-run

     Configures how many revisions delete per cron run.

   - nrd-last-execute

     Get the last time that the node revision delete was made.

   - nrd-set-time

     Configures the frequency with which to delete revisions while cron is
     running.

   - nrd-get-time

     Shows the frequency with which to delete revisions while cron is running.

   - nrd-when-to-delete-time

     Configures the time options for the inactivity time that the revision must
     have to be deleted.

   - nrd-minimum-age-to-delete-time

     Configures time options to know the minimum age that the revision must have
     to be deleted.


MAINTAINERS
-----------

Current maintainers:
 * Adrian Cid Almaguer (adriancid) - https://www.drupal.org/u/adriancid
 * Diosbel Mezquía (diosbelmezquia) - https://www.drupal.org/u/diosbelmezquia


This project has been sponsored by:

 * Ville de Montréal
 * Lullabot
 * Sapient
