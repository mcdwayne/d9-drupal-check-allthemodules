CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module integrates Qwant search engine in Drupal.

For now you are able to:
 * Configure a single search page url
 * Configure number of search results you want to display

REQUIREMENTS
------------

This module requires the following modules:
 * Imagecache external (https://www.drupal.org/project/imagecache_external)

This module requires the PHP cURL library
(https://www.drupal.org/requirements/php/curl).

Your site must be able to contact qwant servers on port 80 to work properly.

You must obtain a Qwant partner ID and HTTP Token.
Please contact Qwant directly (https://www.qwant.com).

INSTALLATION
------------

 * Install and enable this module like any other drupal 8 module.


CONFIGURATION
-------------

 * Enable the qwantsearch module on your main site.
 * Go to the configuration page (admin/config/search/qwantsearch) and choose
   your settings.
 * Go to the block administration page (admin/structure/block) and place the
   Qwantsearch block on your site.
 * Go to the front and start a search using the search block.


MAINTAINERS
-----------

Current maintainers:
 * Bastien Rigon (barig) - https://www.drupal.org/user/2537604
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

This project has been sponsored by:
 * Smile - http://www.smile.fr
