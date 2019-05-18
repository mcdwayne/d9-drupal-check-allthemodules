Preserve page cache.
================================================================================

A variant of Drupal's page cache which ignores cache tags for node pages.

Module is achieving this by not setting cache tags when writing cache entries,
so caches are not invalidated by different cache tags but only expire after
a certain time. Only nodes are kept tagged by node:nid to get invalidated
on update.