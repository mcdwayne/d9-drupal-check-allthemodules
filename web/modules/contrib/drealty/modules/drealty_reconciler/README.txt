--------------------------------------------------------------------
Description
--------------------------------------------------------------------

dRealty Reconciler is a sub-module for Real Estate dRealty Module to help detect and/or clean up the database from inactive listings.

dRealty Reconciler offers the following functionality:

- When the module is enabled, during Cron runs (at the specified interval) the Reconciler will go through the database and:
  1) Get a list of active listings. It will create a Drupal queue to work through. Each of these items in the queue will be checked against the RETS system to see if it still exists there based on your query selections.
     If there's no match found, the listing will be either set inactive or removed from the system.
  2) If you set configuration to REMOVE inactive listings during reconciling process, the module will get a list of inactive listings in the database and add them to the queue.
     When processing the queue, each of these items will be simply removed from the database.

** This project is somewhat a release candidate. It can be used on production sites to facilitate clean up of the listings or setting them as inactive when they are no longer matched against the RETS system.
   It might turn out that it has a few flows/bugs left, but in general it seems to do the job.

----------------------------------
Installation
-----------------------------------

* To install, follow the general directions available at:
http://drupal.org/getting-started/5/install-contrib/modules

--------------------------------------------------------------------
Configuration
--------------------------------------------------------------------

After you enable this module, the configuration form can be found under:
      Drealty => Drealty Import => Configuration Settings.
  Select the time interval when the check should run (usually 1 week is enough). Also, specify if you'd like to set the listings as INACTIVE or to completely remove them from the database
  if they don't have a match in RETS system.
