<?php

namespace Drupal\scheduled_message\Plugin\QueueWorker;

/**
 * Class ScheduledMessageCronWorker.
 *
 * @QueueWorker (
 *   id = "cron_scheduled_message",
 *   title = @Translation("Cron Scheduled Message"),
 *   cron = {"time" = 20}
 * )
 */
class ScheduledMessageCronWorker extends ScheduledMessageWorkerBase {

}
