CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------
Collect provides a simple entity that is able to store any kind of data (binary
include) together with some metadata. This allows you to:
 * store any data without the need to create an entity for it;
 * read and interpret the data at any point with the focus of the current need.

A meta data management layer allows to extract the stored data and map values
to, e.g. Search API so it can be indexed, searched and listed.
There is a demo module which showcases some of the functionality, install it
to get a look at what Collect can do.

REQUIREMENTS
------------

The module requires to run with Drupal 8. Besides that no other requirements
exits.

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8 for further
   information.

CONFIGURATION
-------------
The module does not have anything to configure.

TROUBLESHOOTING
---------------

The module is in heavy development state. Things might change without notice.
So life will be rough and most probably no known solutions will exits.

FAQ
---
TBD

KNOWN ISSUES
------------
If continuous entity capture is enabled and cron is run without an URI context
e.g. through "drush cron", the URI for origin and schema is captured in the
"default" domain, leading to separate records.
Best practice: Make sure cron never runs in default domain context.

MAINTAINERS
-----------
Current maintainers:
 * Christian Häusler (corvus_ch) - https://drupal.org/u/corvus_ch

This project has been sponsored by:
 * MD Systems
   MD Systems is the most active Swiss contributor to Drupal. This includes
   Drupal 8, the well known TMGMT and Shared Content modules as well as more
   than 30 other modules that enhance the user experience in Drupal.
   Visit: http://www.md-systems.ch/ or https://drupal.org/marketplace/md-systems
