<?php

namespace Drupal\linkback\Plugin\QueueWorker;

/**
 * A Linkback Sender that fire the events of sending linkbacks on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_linkback_sender",
 *   title = @Translation("Cron Linkback Sender"),
 *   cron = {"time" = 20}
 * )
 */
class CronLinkbackSender extends LinkbackSender {}
