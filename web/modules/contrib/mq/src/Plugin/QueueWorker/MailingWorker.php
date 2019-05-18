<?php

namespace Drupal\mailing\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

class MailingWorker extends QueueWorkerBase {

  /**
   * Process tasks.
   *
   * @QueueWorker(
   *   id = "send_message_queue",
   *   title = @Translation("My custom queue"),
   *   cron  = {"time" = 60}
   * )
   */
  public function processItem ($data) {
    $message = [
      'message' => $data['message'],
    ];
    $toMail = $data['toMail'];
    $from = $data['mail'];
  }
}