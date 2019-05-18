<?php

namespace Drupal\prefetcher\Commands;

use Drupal\prefetcher\Service\Prefetcher;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for prefetcher module.
 */
class PrefetcherCommands extends DrushCommands {

  /**
   * The prefetcher.
   *
   * @var \Drupal\prefetcher\Service\Prefetcher
   */
  protected $prefetcher;

  /**
   * Constructor.
   *
   * @param \Drupal\prefetcher\Service\Prefetcher $prefetcher
   *   The prefetcher.
   */
  public function __construct(Prefetcher $prefetcher) {
    $this->prefetcher = $prefetcher;
  }

  /**
   * Runs the prefetcher.
   *
   * @command prefetcher:run
   *
   * @option block-size Block size for each request pool.
   * @option limit Limit.
   * @option not-crawled Only process uris which have never been crawled before.
   * @option expiry Include uris to prefetch with the given maximum time in seconds until expiry. When not given, the configuration value will be used.
   * @option silent Suppress status messages in the output stream.
   *
   * @usage drush prefetcher:run --limit="100"
   *   Prefetch 100 uris.
   */
  public function run(array $options = ['block-size' => 0, 'limit' => 0, 'not-crawled' => FALSE, 'expiry' => 0, 'silent' => FALSE]) {
    $this->prefetcher->run($options);
  }

  /**
   * Resets the prefetcher queue.
   *
   * @command prefetcher:reset

   * @usage drush prefetcher:reset
   *   Reset the queue so all uri items are being crawled as soon as possible.
   */
  public function reset() {
    $this->prefetcher->reset();
  }

}
