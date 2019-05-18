--------------------------------------------------------------------
dRealty - The Drupal Real Estate Framework
--------------------------------------------------------------------

dRealty is a real estate framework to import RETS resources (Property,
Agent, Office, OpenHouse, Media) into Drupal as entities.

It's primary function is facilitating the bridge between getting the data
into the site and mapping fields between resources and Drupal.

From here, a user can setup Views for searching via the typical Views method or
using additional modules like ApacheSolr, SearchAPI and Faceted searching.

--------------------------------------------------------------------
System Requirements
--------------------------------------------------------------------

PHP requirements:
* php 5.3.5 or higher - http://www.php.net
* php cURL support - http://www.php.net/manual/en/curl.setup.php

Most hosting vendors come with this by default.

--------------------------------------------------------------------
Installation
--------------------------------------------------------------------

  - Download the phRETS library to path/to/libraries/phrets (https://github.com/troydavisson/PHRETS)
  - Download and enable dRealty
  - Enable optional submodules (dRealty Agent, dRealty Office, dRealty OpenHouse, dRealty Import)

--------------------------------------------------------------------
General Configuration
--------------------------------------------------------------------

All dRealty needs to work is the connection credentials to your RETS provider.
Under Drealty > Drealty Connections, you can add one or more connections to
your providers.

Please note that all vendors operate on slightly varied implementations of RETS.
If all goes well, you are connected to your remote RETS provider and can continue
setting up dRealty.

Most of the action happens here. When you enable the submodules listed above, their
configuration links will appear under each active connection.

Each connection can have resources (Property, Agent, Office, OpenHouse) configured here.
For example, clicking 'Configure Property' will ask you to map the Resource, and then
configuring each of the property types. This interface lets you map all the fields
necessary from RETS data to Drupal fields.

The RETS ID, RETS Key, and Status field are important and specific on a vendor to vendor
basis and will affect the success of your data querying. For most users, Default should appear
as an option (once configured).

This screen also allows you to map the incoming data against defined bundle types to allow
flexibility for displaying them with different field configurations or view modes.

Each submodule type (Agent, Office, OpenHouse) allows for varied bundle configuration(s).

Added Listings minimal reporting functionality based on users reports about missing listings. It will be specific to each connection you create,
and it will only check for Listing properties, not Agents, not Offices or anything else.
Reporting can be found at "/admin/drealty/connections" path, next to each connection there will be a "View Reports" link. The form will allow you to
deactivate the listings which don't have a match in RETS and import the missing ones into your site.

--------------------------------------------------------------------
Configuration - Elysia Cron method
--------------------------------------------------------------------

On hosts like Pantheon, who do not currently allow custom crontab settings,
importing via Drush is not really an option.

It is advised to use dRealty Import module with Elsyia Cron to facilitate getting
data into the site.

The import module facilitates an admin interface to manually queue all or some of
the resources as well as flushing the site.

Note that you may have so many listings that 'Processing Queue' manually from the
backend could timeout while the batch is generating items to process. This is where
Elysia Cron can help process items.

By configuring Elysia Cron to operate the system_cron task frequently, the import
module has a defined cron queue worker to take items from the built-in Drupal Queue
and process as many as it can in one cron run. The cron hook in this module will also
poll for changes in the last few days in RETS, and insert them as queue items to be
processed. You can adjust the timing of these settings under Drealty in the admin
menu.

Every enabled dRealty Resource in each connection will be polled for changes in RETS
however often their cron tasks are set to run in Elysia Cron's configuration.

It is strongly advised to use Elysia Cron to break up the cron tasks into smaller chunks
instead of letting Drupal fire dozens of cron hooks every so often.

--------------------------------------------------------------------
Configuration - Drush method
--------------------------------------------------------------------

On hosts like Acquia, where you can set your own custom crontab settings, Drush
may be a preferred method of import for you.

After enabling and setting up your dRealty connection(s), getting the site
populated with listings is a matter of creating crontab jobs with some basic
Drush commands.

On hosts like Pantheon, who do not support custom crontab configurations, this
method is not possible, and you should use the Elysia Cron method above.

Importing data:
drush -u admin -d rets-import

Flushing Data
drush -u admin -d rets-flush

--------------------------------------------------------------------
Screencast
--------------------------------------------------------------------

Coming soon