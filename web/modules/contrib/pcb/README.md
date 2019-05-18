# INTRODUCTION
At times we need to cache some values which are not related to Drupal config or 
data but are coming from external systems and which don't really need to be 
deleted when clearing (rebuilding) Drupal cache.

This module provides a way to use Drupal cache but still keep it separate from 
drush cr.

* Project page: https://drupal.org/project/pcb

* To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/pcb

# REQUIREMENTS
It requires only Drupal CORE, but:
* For the sub-module pcb_memcache, [memcache](https://www.drupal.org/project/memcache) module is required.
* For the sub-module pcb_redis, [redis](https://www.drupal.org/project/redis) module is required.

# INSTALLATION
Install as any other contrib module, no specific configuration required for
installation.

# CONFIGURATION
There are no configuration as such required for this module. However in order
to use this module there are two ways.

1. Defining a new cache backend through services.yml
>  ```
>  cache.stock:
>    class: Drupal\Core\Cache\CacheBackendInterface
>    tags:
>      - { name: cache.bin, default_backend: cache.backend.permanent_database }
>    factory: cache_factory:get
>    arguments: [stock]
>  ``` 
  
2. Update any existing service via settings file
>  `$settings['cache']['bins']['stock'] = 'cache.backend.permanent_database';`

# FAQ

1. How to clear permanent cache through drush?
>  To clear permanent cache `drush pcbf [bin]` (e.g. `drush pcbf stock`)

2. How to clear permanent cache programmatically?
> \Drupal::service('cache.stock')->deleteAllPermanent();

3. How to clear permanent cache through admin?
> For each cache bin using pcb, there will be a button in Admin -> Development
> -> Performance page (admin/config/development/performance). Use them to clear 
> cache for specific bins through Admin UI.

4. Will cache tags and cache expiration work?
> Yes, everything works as is. Only difference is when we use drush cr or try
> to delete all cache entries.

5. How to know which cache bins are using permanent cache?
>  `drush pcb-list`

6. How to flush cache for all bins using permanent cache?
> `drush pcb-flush-all`
