<?php

namespace Drupal\msg91\Plugin\SmsGateway;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageInterface;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Plugin\SmsGatewayPluginBase;
use Drupal\sms\Message\SmsMessageResult;

/**
 * @SmsGateway(
 *   id = "msg91SmsGateway",
 *   label = @Translation("Msg91 Gateway"),
 * )
 */
class Msg91SmsGateway extends SmsGatewayPluginBase {

  /**
   * Sends an SMS.
   *
   * @param \Drupal\sms\Message\SmsMessageInterface $sms
   *   The sms to be sent.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   *   The result of the sms messaging operation.
   */
  public function send(SmsMessageInterface $sms) {
    $result = new SmsMessageResult();

    foreach ($sms->getRecipients() as $mobile_number) {
      $ret = msg91_send_message($mobile_number, $sms->getMessage(), NULL, NULL);

      $report = (new SmsDeliveryReport())
        ->setRecipient($number)
        ->setTimeDelivered(\Drupal::time()->getRequestTime());

      if (!$ret) {
        $report
          ->setStatus(SmsMessageReportStatus::ERROR)
          ->setStatusMessage('Error');
      }
      else {
        $report
          ->setStatus(SmsMessageReportStatus::DELIVERED)
          ->setStatusMessage('Delivered');
      }
      $result->addReport($report);

    }
    return $result;
  }

}
