<?php

/**
 * @file
 * Contains Drupal\universal_queue\Plugin\QueueWorker\CronUniversalQueue.php
 */

namespace Drupal\universal_queue\Plugin\QueueWorker;


/**
 * A Universal queue CRON runner.
 *
 * @QueueWorker(
 *   id = "universal_queue",
 *   title = @Translation("Cron Universal Queue"),
 *   cron = {"time" = 10}
 * )
 */
class CronUniversalQueue extends UniversalQueueBase {}