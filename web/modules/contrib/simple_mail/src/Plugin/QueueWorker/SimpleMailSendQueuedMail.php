<?php

namespace Drupal\simple_mail\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * @QueueWorker(
 *   id = "simple_mail_queue",
 *   title = @Translation("Simple Mail Send Queued Email"),
 *   cron = {"time" = 60}
 * )
 */
class SimpleMailSendQueuedMail extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($message) {
    simple_mail_send($message['from'], $message['to'], $message['subject'], $message['body']);
  }

}
