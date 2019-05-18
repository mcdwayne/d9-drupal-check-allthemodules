<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerD.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_D",
 *   title="Test worker with '3' position",
 *   cron={"time" = 60, "weight" = -30,}
 * )
 */
class WorkerD extends WorkerBase {}
