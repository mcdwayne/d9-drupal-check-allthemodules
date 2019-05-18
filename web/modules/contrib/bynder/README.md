# Bynder Module

The main goal of this module is to connect to Bynder asset bank
(http://docs.bynder.apiary.io) and manipulate with Bynder media items.

## Requirements and dependencies

The module requires:

- PHP >= 5.6
- Bynder PHP SDK: https://github.com/Bynder/bynder-php-sdk

in order to work.

## Cache configuration

Metaproperties, tags and derivatives information is cached to improve performance
and will be updated periodically during cron. This will happen every 24h by default.
In order to change this put this into your settings.php file:

    // Will cause caches to be updated every 60 seconds.
    $config['bynder.settings']['cache_lifetime'] = 60;

Please note that only requests without queries will be cached. Requests that do (such as
searching for metaproperties based on keywords, ...) will bypass the cache and always request data
from the server. It is, due to the performance reasons, recommended to avoid such requests
and work with locally cached data instead.

One exception to this rule is `getTags()` API call which can potentially be cached even when the
query is used. However, this only applies to certain pre-defined queries
(See \Drupal\bynder\BynderAPI::AUTO\_UPDATED\_TAGS\_QUERIES) and all queries that are using "keyword"
attribute are automatically excluded.
