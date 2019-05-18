<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerC.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_C",
 *   title="Test worker with '5' position",
 *   cron={"time" = 60, "weight" = -10,}
 * )
 */
class WorkerC extends WorkerBase {}
