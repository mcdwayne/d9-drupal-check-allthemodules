CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * FAQ
 * Maintainers

INTRODUCTION
------------
The Cache Consistent module provides a transactional aware cache backend wrapper,
ensuring that cache is synchronized with database transactions.

This module is an attempt to solve the root cause of https://www.drupal.org/node/1679344.

Cache Consistent works best with the database isolation level is set to READ-COMMITTED.
It CAN be used with REPEATABLE-READ, but will in this case only mitigate the problem,
not eliminate it all together. When configured for REPEATABLE-READ, there may also
be occasionally more cache-misses.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/cache_consistent

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/cache_consistent


REQUIREMENTS
------------
 * Drupal 8
 * Transactional PHP module


INSTALLATION
------------
Apply the core patch included with the transactional php module.
Alternatively, see https://www.drupal.org/node/1679344.

A transactional aware cache wrapper, that buffers cache operations until
transaction has been committed.
This method works best with the database isolation level is set to
READ-COMMITTED.
This method CAN be used with REPEATABLE-READ, but will in this case only
mitigate the problem, not eliminate it.


CONFIGURATION
-------------
@code@
// When using MySQL, but not possible to change mysql server configuration,
// the isolation level can be set during database initialization.
$databases['default']['default']['init_commands'] = [
  'isolation_level' => "SET SESSION tx_isolation='READ-COMMITTED'",
];

// When enabled, the Cache Consistent module will attempt to determine which
// cache backends to wrap.

// To disable Cache Consistent without disabling the module, inform
// Cache Consistent that the entire cache system is intrinsically consistent.
// Effectively disables Cache Consistent.
$settings['cache']['consistent'] = TRUE;

// If using a backend that does not need to (or should) be buffered when a
// transaction is in progress, you can explicitly tell Cache Consistent not
// to wrap that backend.
// Warning: If a cache backend is NOT handled by Cache Consistent, the cache
// backend MUST implement the CacheTagsInvalidatorInterface in order to handle
// cache tag invalidations.
// This is because cache tag invalidators can be shared across backends, and
// buffering one without the other will lead to lack of invalidations or double
// invalidations.
$settings['cache']['consistent_backends']['cache.backend.weirdbackend'] = TRUE;

// For READ-COMMITTED setups, you may wan't to inform Cache Consistent about
// the default connection's isolation level.
// Valid options are:
//   0: READ-UNCOMMITTED
//   1: READ-COMMITTED
//   2: REPEATABLE-READ (default)
//   3: SERIALIZABLE
// Inform Cache Consistent that db's isolation level is READ-COMMITTED.
$settings['cache']['isolation_level'] = 1;

@endcode@

EXPERIMENTAL: To optimize cache operations, you can add a "scrubber" to
cache consistent. This will eliminate redundant cache operations within
a transaction.

@code@
  cache_consistent.scrubber:
    class: Drupal\cache_consistent\Cache\CacheConsistentScrubber
    tags:
      - { name: cache_consistent_scrubber }
@endcode@


FAQ
---
Q: Why do I need this?

A: Whenever cache operations occur inside a transaction, the result of the cache
operation will be visible to concurrent requests, even though the transaction
hasn't been committed yet (or even rolled back). See https://www.drupal.org/node/1679344.

Q: But my site is running fine??? ... I think?

A: A lot of sites may run fine without using Cache Consistent. If you're using only
the database as a cache backend, you don't even need Cache Consistent. But the minute
you use another backend for cache, you introduce the possibiliy of a race condition
that can result in the cache being populated with old data. One situation where
this especially can be seen is when using entitycache. This makes a non-database
cache backend very susceptible to a race condition that can result in nodes being
uneditable due to the "The content on this page has either been modified by
another user, or you have already submitted modifications using this form. As a
result, your changes cannot be saved." error, which can then only be resolved
by clearing cache.

Q: But if I just clear the cache, the site works again and runs fine?

A: Yes. That is correct. And if you like that solution, just stick to it.


MAINTAINERS
-----------
Current maintainers:
 * Thomas S. Gielfeldt (gielfeldt) - https://drupal.org/user/366993
