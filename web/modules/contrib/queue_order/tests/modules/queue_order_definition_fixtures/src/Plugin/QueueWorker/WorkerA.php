<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerA.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_A",
 *   title="Test worker with '2' position",
 *   cron={"time" = 60, "weight" = -40,}
 * )
 */
class WorkerA extends WorkerBase {}
