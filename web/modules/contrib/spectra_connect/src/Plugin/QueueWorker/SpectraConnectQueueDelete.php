<?php

namespace Drupal\spectra_connect\Plugin\QueueWorker;

/**
 * Post Spectra Statements on cron run.
 *
 * @QueueWorker(
 *   id = "spectra_connect_queue_delete",
 *   title = @Translation("Spectra Connect Queue Delete"),
 *   cron = {"time" = 10}
 * )
 */
class SpectraConnectQueueDelete extends SpectraConnectQueueDeleteBase {

}
