CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Indexing
 * More info
 * Configuration


INTRODUCTION
------------

Current Maintainer: Reinier Vegter <hr.vegter@gmail.com>

Search API Fast provides you with a drush command to (re)index your
search_api indexes. In the event of a large index, Search API Fast dramatically increases
indexing speed by using a multitude of simultaneous workers, making use of all
you server's cores.

Note this module is dependent on drush, search_api and a unix/linux environment.

INSTALLATION
------------

1. Setup search_api, search_api_solr etc the usual way.
2. Install and enable this module (search_api_fast).


INDEXING
----------------

Run this from the webroot to start indexing:
  - drush sapi-fast [index-name]
    You could use 'top' or 'ps -ef | grep drush' to see all workers spawn.

To reindex:
  - drush sapi-fast [index-name] reindex

To clear the index and then reindex:
  - drush sapi-fast [index-name] clear


MORE INFO
----------------
Workers are started by executing new drush commands from within this module.
After a worker has handled a configurable amount of items, it respawns itself in order to make sure
memory householding doesn't present a problem. This way we won't have to rely on the php garbage collector.


CONFIGURATION
----------------
Optionally set these variables in settings.php to configure Search API Fast:

Drupal 7:
  Amount of simultaneous workers for indexing queues:
    search_api_fast_index_workers: 8
    Note: much more that the amount of CPU-cores doesn't help much.

  Amount of batches to be handled by each worker before it's respawned.
  If changing this, you should take memory consumption into account (more batches mean higher amount of non-collected
  memory pages).
    search_api_fast_max_batches_worker_respawn: 4

  Batch size (items to index).
  Each worker handles batches from it's own queue.
    search_api_fast_worker_batch_size: 100

Drupal 8 example:
  $config['search_api_fast.performance']['index_workers'] = 8;
  $config['search_api_fast.performance']['worker_batch_size'] = 100;
  $config['search_api_fast.performance']['max_batches_worker_respawn'] = 4;
  $config['search_api_fast.performance']['drush'] = '/opt/mycooldrush/drush';

