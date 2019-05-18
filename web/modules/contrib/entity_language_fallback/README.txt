CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Sponsors
 * Maintainers

INTRODUCTION
------------
This module allow administrators to configure a prioritised list of fallback
languages per language. The fallback languages are used for entity view /
entity upcast.

REQUIREMENTS
------------
 * Drupal 8.3 or later.
 * Language module.

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

CONFIGURATION
-------------
After installation go to admin/config/regional/language, edit one of the
languages and configure the fallback languages.

USAGE WITH SEARCH API
-------------
This module contains 2 Search API components:
processor (https://www.drupal.org/docs/8/modules/search-api/getting-started/processors) and datasource (https://www.drupal.org/docs/8/modules/search-api/developer-documentation/providing-a-new-datasource-plugin)
Using datasource is preferred way, since it will correctly report number of items to index. Use processor only if you cannot change datasource setting.

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