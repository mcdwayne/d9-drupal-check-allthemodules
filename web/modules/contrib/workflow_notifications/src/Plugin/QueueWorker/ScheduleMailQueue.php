<?php

namespace Drupal\workflow_notifications\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * process mail to the scheduled entities.
 *
 * @QueueWorker(
 *   id = "workflow_scheduled_entity_mail",
 *   title = @Translation("Workflow mail trigger for scheduled entities."),
 *   cron = {"time" = 60},
 * )
 */
class ScheduleMailQueue extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $day = $data['entity']->days ? $data['entity']->days : 0;
    if (!empty($day)) {
      $start_time = strtotime("+" . $day . " days 12:00:00 am");
      $end_time = strtotime("+" . $day . " days 11:59:59 pm");
      if($data['notify'] == "mail") {
        if(function_exists("_workflow_notifications_send_mail_to_all")) {
          _workflow_notifications_send_mail_to_all($start_time, $end_time, $data['entity']);
        }        
      } else if ($data['notify'] == "sms") {
        if(function_exists("_workflow_sms_notify_send_sms_to_all")) {
          _workflow_sms_notify_send_sms_to_all($start_time, $end_time, $data['entity']);
        }
      }      
    }
  }
}
