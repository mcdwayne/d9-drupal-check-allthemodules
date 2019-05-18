<?php
namespace Drupal\elastic_email\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\elastic_email\Plugin\Mail\ElasticEmailMailSystem;

/**
 * Processes Mail Queue for Elastic Email.
 *
 * @QueueWorker(
 *   id = "elastic_email_process_queue",
 *   title = @Translation("Elastic Email Process Queue"),
 *   cron = {"time" = 5}
 * )
 */
class ElasticEmailProcessQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $mail = new ElasticEmailMailSystem();
    $mail->send($data);
  }

}