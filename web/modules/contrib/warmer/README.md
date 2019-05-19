CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module provides all the necessary infrastructure to orchestrate your cache warming processes.

You can warm the cache of your critical entities (and more!) right after you deploy to production. 
Additionally cron will keep them warm for you.

All these operations are executed asynchronously to avoid impacting the users.

 * For a full description of the module visit:
  https://www.drupal.org/project/warmer

 * To submit bug reports and feature suggestions, or to track changes visit:
  https://www.drupal.org/project/issues/warmer


REQUIREMENTS
------------
 * XML Sitemap parser library - https://github.com/VIPnytt/SitemapParser
 * This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the warmer module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------

####Warmer plugins
By itself the module does nothing. It needs other modules (contrib or custom) to provide `@Warmer` plugins. 
Each warmer plugin is in charge of warming a different type of items. 
You could have a warmer (a plugin) dedicated to the entity cache, another one for the JSON:API 
normalizations (via [JSON:API Boost](https://www.drupal.org/project/jsonapi_boost)), another one that hits URLs to warm the CDN cache, etc.

A warmer plugin consists of:
 * A settings form.
 * A method to build a list of item IDs to warm. 
 This works in batches to avoid overloading the system. Ex: entity ID, full URLs.
 * A method to trigger the warming operation. Ex: entity load, an external HTTP request.
 
####CDN Warmer
This module also provides a sub-module that includes a `@Warmer` for the page cache,
Varnish, and edge cache, as it is a very common task. 
Warmer will make HTTP requests to the configured URLs to keep these caches warm.

Configure it with a list of URLs to keep warm. You can use any URL format like: `entity:user/1`, `/node/1`, `https://example.org/foo`.

Alternatively, you can collect the URLs to warm from a Sitemap. Configure the warmer with the sitemap URL and (optionally) filter out the URLs with low priority.

####Entity Warmer
This module also provides a sub-module that includes a `@Warmer` for the entity cache, 
as it is a very common task. This is a great example to develop your own warmers!

####Schedule cache warming
You can manually enqueue the cache warming operations using the UI.
Additionally `hook_cron` will schedule the cache warming with a configurable frequency.

####Warm using Drush
One of the primary uses of this module is to warm your caches as part of the deployment script of your site. 
You can use Drush to do that.

List all the available warmers with the `drush warmer:list` command.

Then warm the caches by selecting the warmers to run:

`drush warmer:enqueue entity,resource,custom --run-queue`

MAINTAINERS
-----------

 * Mateu Aguiló Bosch (e0ipso) - https://www.drupal.org/u/e0ipso

Supporting organizations:

 * Lullabot - https://www.drupal.org/lullabot
