<?php

namespace Drupal\streamy\Plugin\QueueWorker;

use Drupal\streamy\Fallback\StreamyFallbackQueueWorker;

/**
 * A
 *
 * @QueueWorker(
 *   id = "streamy_fallback_queue_worker",
 *   title = @Translation("Streamy Fallback Queue"),
 *   cron = {"time" = 60}
 * )
 */
class CronFallbackQueueWorker extends StreamyFallbackQueueWorker {

}
