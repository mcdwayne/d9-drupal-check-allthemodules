<?php

namespace Drupal\pcb\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheFactoryInterface;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * A Drush commandfile for pcb module.
 */
class PcbCommands extends DrushCommands {

  /**
   * Cache Factory.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  private $cacheFactory;

  /**
   * PcbCommands constructor.
   *
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cache_factory
   *   Cache Factory.
   */
  public function __construct(CacheFactoryInterface $cache_factory) {
    $this->cacheFactory = $cache_factory;
  }

  /**
   * Flush permanent cache bin.
   *
   * @param string $bin
   *   Bin to flush cache of.
   * @usage pcb-flush bin
   *   Flush cache for particular bin.
   *
   * @command pcb:flush
   * @aliases pcbf, permanent-cache-bin-flush
   */
  public function flush(string $bin) {
    try {
      $cache = $this->cacheFactory->get($bin);
      if (method_exists($cache, 'deleteAllPermanent')) {
        $cache->deleteAllPermanent();
        $this->logger()->success(dt('Deleted all cache for @bin.', ['@bin' => $bin]));
      }
      else {
        $this->logger()->error(dt('@bin bin is not using pcb.', ['@bin' => $bin]));
      }
    }
    catch (\Exception $e) {
      $this->logger()->error(dt('@bin not a valid cache bin.', ['@bin' => $bin]));
    }
  }

  /**
   * Flush cache for all bins using permanent cache backend.
   *
   * @usage pcb-flush-all
   *   Flush cache for all bins using permanent cache backend.
   *
   * @command pcb:flush-all
   * @aliases pcb-flush-all, permanent-cache-bin-flush-all
   */
  public function flushAll() {
    if (!$this->io()->confirm(dt('Are you sure you want to flush all permanent cache bins?'))) {
      throw new UserAbortException();
    }

    foreach (Cache::getBins() as $bin => $backend) {
      if (method_exists($backend, 'deleteAllPermanent')) {
        $backend->deleteAllPermanent();
        $this->logger()->success(dt('Flushed all cache for @bin.', ['@bin' => $bin]));
      }
    }
  }

  /**
   * List permanent cache bins.
   *
   * @usage pcb-list
   *   Usage description
   *
   * @command pcb:list
   * @aliases pcb-list, permanent-cache-bin-list
   */
  public function listBins() {
    $bins = Cache::getBins();

    foreach ($bins AS $bin => $object) {
      if (method_exists($object, 'deleteAllPermanent')) {
        $this->io()->writeln($bin);
      }
    }

    $this->io()->writeln('');
  }

}
