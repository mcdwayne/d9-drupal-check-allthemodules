<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerF.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_F",
 *   title="Test worker with 'last' position",
 *   cron={"time" = 60, "weight" = 10,}
 * )
 */
class WorkerF extends WorkerBase {}
