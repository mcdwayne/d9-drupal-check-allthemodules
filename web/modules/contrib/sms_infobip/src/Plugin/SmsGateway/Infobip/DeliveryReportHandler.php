<?php

namespace Drupal\sms_infobip\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;

/**
 * Handles the Infobip delivery reports and turns it into SmsMessageResult.
 */
class DeliveryReportHandler extends InfobipResponseHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function handle($body) {
    $response = Json::decode($body);
    $reports = [];
    if (isset($response['results'])) {
      foreach ($response['results'] as $result) {
        $report = (new SmsDeliveryReport())
          ->setRecipient($result['to'])
          ->setMessageId($result['messageId'])
          ->setTimeQueued(strtotime($result['sentAt']))
          ->setTimeDelivered(strtotime($result['doneAt']))
          ->setStatus($this->mapStatus($result['status']))
          ->setStatusMessage($result['status']['description']);
        if ($report->getStatus() === SmsMessageReportStatus::DELIVERED) {
          $report->setStatusTime($report->getTimeDelivered());
        }
        else {
          $report->setStatusTime($report->getTimeQueued());
        }
        $reports[] = $report;
      }
    }
    return (new SmsMessageResult())->setReports($reports);
  }

}
