<?php

namespace Drupal\peytz_mail\Plugin\QueueWorker;

/**
 * A worker that subscribes user on Cron run.
 *
 * @QueueWorker(
 *   id = "peytz_mail_subscribe_worker_cron",
 *   title = @Translation("Cron Peytz Mail subscriber"),
 *   cron = {"time" = 60}
 * )
 */
class PeytzMailSubscribeWorkerCron extends PeytzMailSubscribeWorkerBase {}
