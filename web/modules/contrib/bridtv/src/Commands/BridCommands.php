<?php

namespace Drupal\bridtv\Commands;

use Drupal\bridtv\BridSync;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for Brid.TV integration.
 */
class BridCommands extends DrushCommands {

  /**
   * The sync service.
   *
   * @var \Drupal\bridtv\BridSync
   */
  protected $bridSync;

  /**
   * BridCommands constructor.
   *
   * @param \Drupal\bridtv\BridSync $sync
   */
  public function __construct(BridSync $sync) {
    $this->bridSync = $sync;
  }

  /**
   * Synchronizes the video data with the Brid.TV service.
   *
   * @command bridtv:sync
   *
   * @option limit The number of queue items to synchronize.
   *
   * @usage drush bridtv:sync --limit=10
   *   Synchronizes 10 video items.
   */
  public function sync(array $options = ['limit' => -1]) {
    $sync = $this->bridSync;
    $limit = isset($options['limit']) ? (int) $options['limit'] : -1;

    $sync->run($limit);
  }

}
