<?php

namespace Drupal\cache_consistent\Cache;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\transactionalphp\TransactionalPhpAwareTrait;
use Drupal\transactionalphp\TransactionalPhpEvent;
use Drupal\transactionalphp\TransactionalPhpEvents;
use Drupal\transactionalphp\TransactionalPhpIndexerAwareTrait;
use Drupal\transactionalphp\TransactionSubscriberTrait;
use Gielfeldt\TransactionalPHP\Connection;
use Gielfeldt\TransactionalPHP\Operation;
use Gielfeldt\TransactionalPHP\Indexer;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class CacheConsistentBuffer.
 *
 * @package Drupal\cache_consistent\Cache
 *
 * @ingroup cache_consistent
 */
class CacheConsistentBuffer implements CacheConsistentBufferInterface, EventSubscriberInterface {

  use CacheBackendAwareTrait;
  use CacheTagsChecksumAwareTrait;
  use TransactionSubscriberTrait {
    getSubscribedEvents as traitGetSubscribedEvents;
    setContainer as traitSetContainer;
  }
  use TransactionalPhpAwareTrait;
  use TransactionalPhpIndexerAwareTrait;

  /**
   * An array of operation indices which contain deleteAll() operation.
   *
   * @var array
   */
  protected $deleteAllIndex = [-1];

  /**
   * An array of operation indices which contain invalidateAll() operation.
   *
   * @var array
   */
  protected $invalidateAllIndex = [-1];

  /**
   * An array of operation indices which contain removeBin() operation.
   *
   * @var array
   */
  protected $removeBinIndex = [-1];

  /**
   * The cache scrubber manager to use.
   *
   * @var \Drupal\cache_consistent\Cache\CacheConsistentScrubberManager
   */
  protected $scrubberManager = NULL;

  /**
   * The current depth.
   *
   * @var int
   */
  protected $depth = 0;

  /**
   * The cache bin of this buffer.
   *
   * @var string
   */
  protected $bin;

  /**
   * CacheConsistentBuffer constructor.
   *
   * @param string $bin
   *   The name of the cache bin.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Gielfeldt\TransactionalPHP\Indexer $transactional_php_indexer
   *   The transactional php connection.
   * @param \Drupal\Core\Cache\CacheTagsChecksumInterface $checksum_provider
   *   The checksum provider to use.
   *
   * @codeCoverageIgnore
   *   Too difficult to test constructors.
   */
  public function __construct($bin, CacheBackendInterface $cache_backend, Indexer $transactional_php_indexer, CacheTagsChecksumInterface $checksum_provider) {
    $this->bin = $bin;
    $this->cacheBackend = $cache_backend;
    $this->setTransactionalPhpIndexer($transactional_php_indexer);
    $this->setTransactionalPhp($transactional_php_indexer->getConnection());
    $this->trackConnection($this->transactionalPhp->getTrackedConnection());
    $this->setChecksumProvider($checksum_provider);
    $this->depth = $this->transactionalPhp->getTrackedConnection()->transactionDepth();
  }

  /**
   * {@inheritdoc}
   *
   * We exclude this from test coverage, as getSubscribedEvents is tested by
   * Drupal in general.
   *
   * @codeCoverageIgnore
   */
  static public function getSubscribedEvents() {
    $events = static::traitGetSubscribedEvents();
    $events[TransactionalPhpEvents::PRE_COMMIT][] = 'scrubOperations';
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container = NULL) {
    $this->traitSetContainer($container);
    if ($container && $container->has('cache_consistent.scrubber.manager')) {
      $this->scrubberManager = $this->container->get('cache_consistent.scrubber.manager');
    }
  }

  /**
   * Use a scrubber to scrub operations if applicable.
   *
   * @param \Drupal\transactionalphp\TransactionalPhpEvent $event
   *   The transactional event.
   */
  public function scrubOperations(TransactionalPhpEvent $event) {
    // No scrubber manager, don't do anything.
    if (!$this->scrubberManager) {
      return;
    }

    // Ensure we're handling the correct event.
    if ($event->getSubject() != $this->transactionalPhp) {
      return;
    }

    // Get operations from event and perform scrubbing.
    $operations = &$event->getArgument('operations');

    // Only pass on the operations from this cache buffer to the scrubbers.
    $buffered_operations = $this->transactionalPhpIndexer->getOperations();
    $buffered_operations = array_intersect_key($buffered_operations, $operations);

    // Perform actual scrubbing.
    $scrubbed_operations = $this->scrubberManager->scrub($buffered_operations);

    // Remove operations properly after scrubbing.
    foreach ($buffered_operations as $idx => $operation) {
      if (!isset($scrubbed_operations[$idx])) {
        unset($operations[$idx]);
        $this->transactionalPhp->removeOperation($operation);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transactionDepth() {
    return $this->depth;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheBackend() {
    return $this->cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public function startTransactionEvent($new_depth) {
    $this->depth = $new_depth;
    $this->deleteAllIndex[$new_depth] = -1;
    $this->invalidateAllIndex[$new_depth] = -1;
    $this->removeBinIndex[$new_depth] = -1;
  }

  /**
   * {@inheritdoc}
   */
  public function commitTransactionEvent($new_depth) {
    $this->depth = $new_depth;
    foreach ($this->deleteAllIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        $this->deleteAllIndex[$new_depth] = $idx;
        unset($this->deleteAllIndex[$depth]);
      }
    }
    foreach ($this->invalidateAllIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        $this->invalidateAllIndex[$new_depth] = $idx;
        unset($this->invalidateAllIndex[$depth]);
      }
    }
    foreach ($this->removeBinIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        $this->removeBinIndex[$new_depth] = $idx;
        unset($this->removeBinIndex[$depth]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollbackTransactionEvent($new_depth) {
    $this->depth = $new_depth;
    foreach ($this->deleteAllIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        unset($this->deleteAllIndex[$depth]);
      }
    }
    foreach ($this->invalidateAllIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        unset($this->invalidateAllIndex[$depth]);
      }
    }
    foreach ($this->removeBinIndex as $depth => $idx) {
      if ($depth > $new_depth) {
        unset($this->removeBinIndex[$depth]);
      }
    }
  }

  /**
   * Prepares a cached item.
   *
   * Checks that items are either permanent or did not expire, and returns data
   * as appropriate.
   *
   * @param array $operations
   *   Operations from the operations buffer.
   * @param bool $allow_invalid
   *   (optional) If TRUE, cache items may be returned even if they have expired
   *   or been invalidated.
   *
   * @return mixed
   *   The item with data as appropriate or FALSE if there is no
   *   valid item to load.
   */
  protected function prepareItem($cid, $operations, $allow_invalid = FALSE) {
    if (!$operations) {
      if ($this->deleteAllIndex[$this->depth] >= 0 || $this->removeBinIndex[$this->depth] >= 0) {
        return FALSE;
      }
      elseif ($this->invalidateAllIndex[$this->depth] >= 0 && !$allow_invalid) {
        return FALSE;
      }
      else {
        return NULL;
      }
    }

    // array_pop() with key-value pair.
    $operation = end($operations);
    $idx = key($operations);
    unset($operations[$idx]);

    if ($idx <= $this->deleteAllIndex[$this->depth] || $idx <= $this->removeBinIndex[$this->depth]) {
      return FALSE;
    }

    $operation_name = $operation->getMetadata('operation');
    switch ($operation_name) {
      case 'delete':
        return FALSE;

      case 'deleteMultiple':
        $cids = $operation->getMetadata('cids');
        if (!in_array($cid, $cids)) {
          throw new \RuntimeException('Cache id: "$cid" not found in deleteMultiple cid set');
        }

        return FALSE;

      case 'invalidate':
        return $allow_invalid ? $this->prepareItem($cid, $operations, $allow_invalid) : FALSE;

      case 'invalidateMultiple':
        $cids = $operation->getMetadata('cids');
        if (!in_array($cid, $cids)) {
          throw new \RuntimeException('Cache id: "$cid" not found in invalidateMultiple cid set');
        }

        return $allow_invalid ? $this->prepareItem($cid, $operations, $allow_invalid) : FALSE;

      case 'set':
        $item = $operation->getMetadata('data');
        // Check if item has been invalidated.
        $item->valid = ($item->expire == Cache::PERMANENT || $item->expire >= $this->getRequestTime());
        $item->valid = $item->valid && $idx >= $this->invalidateAllIndex[$this->depth];

        return $item->valid || $allow_invalid ? $item : FALSE;

      case 'setMultiple':
        $items = $operation->getMetadata('items');
        if (!isset($items[$cid])) {
          throw new \RuntimeException('Cache id: "$cid" not found in items multiple set');
        }
        $item = (object) $items[$cid];

        // Check if item has been invalidated.
        $item->valid = ($item->expire == Cache::PERMANENT || $item->expire >= $this->getRequestTime());
        $item->valid = $item->valid && $idx >= $this->invalidateAllIndex[$this->depth];

        return $item->valid || $allow_invalid ? $item : FALSE;

      default:
        // Extremely defensive coding, cannot test.
        // @codeCoverageIgnoreStart
        throw new \RuntimeException('Invalid operation found: ' . $operation_name);
      // @codeCoverageIgnoreEnd
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($cid, $allow_invalid = FALSE) {
    $cids = [$cid];
    $items = $this->getMultiple($cids, $allow_invalid);
    $item = reset($items);
    return $item ? $item : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMultiple(&$cids, $allow_invalid = FALSE) {
    $items = [];

    foreach ($cids as $cid) {
      $operations = $this->transactionalPhpIndexer->lookup($cid);
      $item = $this->prepareItem($cid, $operations, $allow_invalid);
      if (isset($item)) {
        $items[$cid] = $item;
      }
    }

    $cids = array_diff($cids, array_keys($items));

    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function set($cid, $data, $expire = Cache::PERMANENT, array $tags = []) {
    assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($tags)', 'Cache Tags must be strings.');
    $tags = array_unique($tags);
    // Sort the cache tags so that they are stored consistently in the database.
    sort($tags);
    $data = unserialize(serialize($data));

    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $cid, $data, $expire, $tags) {
        $backend->set($cid, $data, $expire, $tags);
      })
      ->setMetadata('operation', 'set')
      ->setMetadata('cid', $cid)
      ->setMetadata('data', (object) [
        'cid' => $cid,
        'data' => $data,
        'expire' => $expire,
        'tags' => $tags,
        'checksum' => $this->checksumProvider ? $this->checksumProvider->getCurrentChecksum($tags) : [],
        'buffered' => TRUE,
      ])
      ->onBuffer(function (Operation $operation) use ($indexer, $cid, $data) {
        $indexer->index($operation, $cid);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;

  }

  /**
   * {@inheritdoc}
   */
  public function setMultiple(array $items = []) {
    $items = unserialize(serialize($items));

    // Prepare items for buffering/insertion.
    foreach ($items as $cid => &$item) {
      $item += array(
        'expire' => CacheBackendInterface::CACHE_PERMANENT,
        'tags' => array(),
        'buffered' => TRUE,
      );
      assert('\Drupal\Component\Assertion\Inspector::assertAllStrings($item[\'tags\'])', 'Cache Tags must be strings.');
      $item['tags'] = array_unique($item['tags']);
      // Sort the cache tags so that they are stored consistently in the DB.
      sort($item['tags']);
      $item['checksum'] = $this->checksumProvider ? $this->checksumProvider->getCurrentChecksum($item['tags']) : [];
    }

    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $items) {
        $backend->setMultiple($items);
      })
      ->setMetadata('operation', 'setMultiple')
      ->setMetadata('items', $items)
      ->onBuffer(function (Operation $operation) use ($indexer, $items) {
        foreach ($items as $cid => $data) {
          $indexer->index($operation, $cid);
        }
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($cid) {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $cid) {
        $backend->delete($cid);
      })
      ->setMetadata('operation', 'delete')
      ->setMetadata('cid', $cid)
      ->onBuffer(function ($operation) use ($indexer, $cid) {
        $indexer->index($operation, $cid);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple(array $cids) {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $cids) {
        $backend->deleteMultiple($cids);
      })
      ->setMetadata('operation', 'deleteMultiple')
      ->setMetadata('cids', $cids)
      ->onBuffer(function ($operation) use ($indexer, $cids) {
        foreach ($cids as $cid) {
          $indexer->index($operation, $cid);
        }
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $index = &$this->deleteAllIndex[$this->depth];
    $operation = (new Operation())
      ->onCommit(function() use ($backend) {
        $backend->deleteAll();
      })
      ->setMetadata('operation', 'deleteAll')
      ->onBuffer(function (Operation $operation, Connection $connection = NULL) use (&$index, $indexer) {
        $index = $operation->idx($connection);
        $indexer->index($operation);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate($cid) {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $cid) {
        $backend->invalidate($cid);
      })
      ->setMetadata('operation', 'invalidate')
      ->setMetadata('cid', $cid)
      ->onBuffer(function (Operation $operation) use ($indexer, $cid) {
        $indexer->index($operation, $cid);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateMultiple(array $cids) {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $cids) {
        $backend->invalidateMultiple($cids);
      })
      ->setMetadata('operation', 'invalidateMultiple')
      ->setMetadata('cids', $cids)
      ->onBuffer(function ($operation) use ($indexer, $cids) {
        foreach ($cids as $cid) {
          $indexer->index($operation, $cid);
        }
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateAll() {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $index = &$this->invalidateAllIndex[$this->depth];
    $operation = (new Operation())
      ->onCommit(function () use ($backend) {
        $backend->invalidateAll();
      })
      ->setMetadata('operation', 'invalidateAll')
      ->onBuffer(function (Operation $operation, Connection $connection = NULL) use (&$index, $indexer) {
        $index = $operation->idx($connection);
        $indexer->index($operation);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    $backend = $this->cacheBackend;
    $operation = (new Operation())
      ->onCommit(function() use ($backend) {
        $backend->garbageCollection();
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function removeBin() {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $index = &$this->removeBinIndex[$this->depth];
    $operation = (new Operation())
      ->onCommit(function() use ($backend) {
        $backend->removeBin();
      })
      ->setMetadata('operation', 'removeBin')
      ->onBuffer(function (Operation $operation, Connection $connection = NULL) use (&$index, $indexer) {
        $index = $operation->idx($connection);
        $indexer->index($operation);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateTags(array $tags) {
    $backend = $this->cacheBackend;
    $indexer = $this->transactionalPhpIndexer;
    $operation = (new Operation())
      ->onCommit(function() use ($backend, $tags) {
        if ($backend instanceof CacheTagsInvalidatorInterface) {
          $backend->invalidateTags($tags);
        }
      })
      ->setMetadata('operation', 'invalidateTags')
      ->setMetadata('tags', $tags)
      ->onBuffer(function (Operation $operation) use ($indexer) {
        $indexer->index($operation);
      });
    $this->transactionalPhp->addOperation($operation);
    return TRUE;
  }

  /**
   * Wrapper method for REQUEST_TIME constant.
   *
   * @return int
   *   The request time.
   */
  protected function getRequestTime() {
    return defined('REQUEST_TIME') ? REQUEST_TIME : (int) $_SERVER['REQUEST_TIME'];
  }

}
