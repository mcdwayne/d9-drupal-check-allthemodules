<?php

namespace Drupal\spectra_connect\Plugin\QueueWorker;

/**
 * Post Spectra Statements on cron run.
 *
 * @QueueWorker(
 *   id = "spectra_connect_queue_post",
 *   title = @Translation("Spectra Connect Queue Post"),
 *   cron = {"time" = 10}
 * )
 */
class SpectraConnectQueuePost extends SpectraConnectQueuePostBase {

}
