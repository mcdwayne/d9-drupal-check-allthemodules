<?php

namespace Drupal\brightcove\Commands;

use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Exception\BrightcoveUtilException;
use Drupal\Core\Queue\QueueFactory;
use Drush\Commands\DrushCommands;

/**
 * Drush 9.x commands for Brightcove Video Connect.
 */
class BrightcoveCommands extends DrushCommands {

  /**
   * The queue service.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * BrightcoveCommands constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue service.
   */
  public function __construct(QueueFactory $queueFactory) {
    $this->queueFactory = $queueFactory;
  }

  /**
   * Initiates a Brightcove-to-Drupal sync by adding API clients to the queue.
   *
   * @command brightcove:sync-all
   * @aliases brightcove-sync-all,bcsa
   */
  public function syncAll() {
    $this->output()->writeln('Initiating Brightcove-to-Drupal sync...');
    try {
      BrightcoveUtil::runStatusQueues('sync', $this->queueFactory);
      drush_backend_batch_process();
      $this->logger()->notice('Sync complete.');
    }
    catch (BrightcoveUtilException $e) {
      $this->logger()->error($e->getMessage());
      watchdog_exception('brightcove', $e);
    }
  }

}
