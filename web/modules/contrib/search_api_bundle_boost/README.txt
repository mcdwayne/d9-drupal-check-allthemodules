CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Developers
 * Troubleshooting
 * Sponsors
 * Maintainers

INTRODUCTION
------------
Search API core does not support boosting of specific entity bundles like
Apache Solr module did in Drupal 7. This module tries to solve this by allowing
administrators to configure the document boost per entity bundle.

REQUIREMENTS
------------
 * Search API (https://www.drupal.org/project/search_api)
 * Search API Solr (https://www.drupal.org/project/search_api_solr)

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. 
   See: https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------
 * Edit your existing index and choose the Processors tab.
 * Enable the plugin "Search API Bundle Boost".
 * Configure boost level per entity bundle.
 * Save
 * Reindex all entities in your index.

DEVELOPERS
----------
-

TROUBLESHOOTING
---------------
-

SPONSORS
--------
 * FFW - https://ffwagency.com

MAINTAINERS
-----------
Current maintainers:
 * Jens Beltofte (beltofte) - https://drupal.org/u/beltofte
