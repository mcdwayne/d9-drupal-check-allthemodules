<?php

namespace Drupal\queue_monitor\Commands;

use Drupal\queue_monitor\Queue\QueueProcess;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Drush\Utils\StringUtils;

/**
 * Class QueueMonitorCommands
 *
 * @package Drupal\queue_monitor\Commands
 */
class QueueMonitorCommands extends DrushCommands {

  protected $queueProcess;

  /**
   * Constructs a new QueueMonitorConsoleCommand object.
   *
   * @param \Drupal\queue_monitor\Queue\QueueProcess $queueProcess
   */
  public function __construct(QueueProcess $queueProcess) {
    parent::__construct();
    $this->queueProcess = $queueProcess;
  }

  /**
   * @return \Drupal\queue_monitor\Queue\QueueProcess
   */
  public function getQueueProcess() {
    return $this->queueProcess;
  }

  /**
   * Run queue by name.
   *
   * @param       $name
   *
   * @command queue_monitor:run
   * @usage   queue_monitor:run myqueue
   *
   * @throws \Exception
   */
  public function queueRun($name) {
    while (TRUE) {
      $this->getQueueProcess()->queueRun($name);
    }
  }

  /**
   * Run all queue.
   *
   * @command queue_monitor:runall
   * @usage   queue_monitor:runall
   *
   * @throws \Exception
   */
  public function queueRunAll() {
    while (TRUE) {
      $this->getQueueProcess()->queueRunAll();
      $config = \Drupal::config('queue_monitor.settings');
      sleep($config->get('sleep'));
    }
  }
}
