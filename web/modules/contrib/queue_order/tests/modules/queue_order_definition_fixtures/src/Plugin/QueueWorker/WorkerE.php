<?php

namespace Drupal\queue_order_definition_fixtures\Plugin\QueueWorker;

/**
 * Class WorkerE.
 *
 * @package Drupal\queue_order_definition_fixtures\Plugin\QueueWorker
 *
 * @QueueWorker(
 *   id="queue_order_worker_E",
 *   title="Test worker with '4' position",
 *   cron={"time" = 60, "weight" = -20,}
 * )
 */
class WorkerE extends WorkerBase {}
