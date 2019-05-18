CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Manage synonyms
 * Export synonyms
 * Developers
 * Troubleshooting
 * Sponsors
 * Maintainers

INTRODUCTION
------------
This module let editors or administrators manage synonyms for Search API
directly in Drupal.

Synonyms can be export using the build in Drupal Console command.
Drush command and automatic export using Drupal cron job is in development.

The module support the synonyms.txt format used in Apache Solr.
Other formats can be added using the Export plugin annotation.

REQUIREMENTS
------------
* No requirements.

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

MANAGE SYNONYMS
---------------
After installation can you start managing your synonyms and spelling errors
at admin/config/search/search-api-synonyms.

EXPORT SYNONYMS
---------------

Drupal Console
--------------

Export the added synonyms using the Drupal Console command:

- drupal searchapi:synonym:export

Execute the command with --help to see the different options.

Drush 8 and previous versions
-----------------------------

Export synonyms using the Drush command:

- drush search-api-synonym-export

- drush sapi-syn-ex

Drush 9
-------

Export synonyms using the Drush command:

- drush search-api-synonym:export

- drush sapi-syn:export

- drush sapi-syn-ex

Examples
--------
Replace [COMMAND] with one of the above Drupal Console / Drush commands.

Export all Danish synonyms and spelling errors in the Solr format.

- [COMMAND] --plugin=solr --langcode=da

Export all Danish spelling errors in the Solr format.

- [COMMAND] --plugin=solr --langcode=da --type=spelling_error

Export all Danish synonyms without white spaces in the Solr format.

- [COMMAND] --plugin=solr --langcode=da --type=synonym --filter=nospace

Cron
----

Export using Drupal cron is supported. See the settings in /admin/config/search/search-api-synonyms/settings.

DEVELOPERS
----------

The Search API Synonym module provides the following ways for developers to
extend the functionality:

- Plugins
  Export plugin - see the annotation and the Solr plugin:
  - Drupal\search_api_synonym\Annotation\SearchApiSynonymExport
  - Drupal\search_api_synonym\Plugin\search_api_synonym\export\Solr

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
