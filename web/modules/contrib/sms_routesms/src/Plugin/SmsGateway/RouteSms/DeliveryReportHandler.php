<?php

namespace Drupal\sms_routesms\Plugin\SmsGateway\RouteSms;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms_gateway_base\Plugin\SmsGateway\ResponseHandlerInterface;

/**
 * Handles delivery reports for the RouteSMS Gateway.
 */
class DeliveryReportHandler implements ResponseHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function handle($post) {
    return $this->parseDeliveryReport(Json::decode($post));
  }

  /**
   * Processes RouteSMS delivery reports into SMS delivery report objects.
   *
   * @param array $post
   *   An array containing delivery information on the message.
   *
   * @return \Drupal\sms\Message\SmsMessageResultInterface
   */
  public function parseDeliveryReport(array $post) {
    $reports = [];
    if (isset($post['sMobileNo'])) {
      $report = (new SmsDeliveryReport())
        ->setRecipient($post['sMobileNo'])
        ->setMessageId(trim($post['sMessageId']))
        ->setTimeQueued(\DateTime::createFromFormat("Y-m-d H:i:s", $post['dtSubmit'], timezone_open('UTC'))->getTimestamp())
        ->setTimeDelivered(\DateTime::createFromFormat("Y-m-d H:i:s", $post['dtDone'], timezone_open('UTC'))->getTimestamp())
        ->setStatus(self::$statusMap[$post['sStatus']])
        ->setStatusMessage($post['sStatus']);
      if ($report->getStatus() === SmsMessageReportStatus::DELIVERED) {
        $report->setStatusTime($report->getTimeDelivered());
      }
      else {
        $report->setStatusTime($report->getTimeQueued());
      }
      $reports[] = $report;
    }
    return (new SmsMessageResult())->setReports($reports);
  }

  // 'UNKNOWN', 'ACKED', 'ENROUTE', 'DELIVRD', 'EXPIRED', 'DELETED',
  // 'UNDELIV', 'ACCEPTED', 'REJECTD'.
  protected static $statusMap = [
    'DELIVRD' => SmsMessageReportStatus::DELIVERED,
    'UNDELIV' => SmsMessageReportStatus::REJECTED,
    'REJECTD' => SmsMessageReportStatus::REJECTED,
    'EXPIRED' => SmsMessageReportStatus::EXPIRED,
    'UNKNOWN' => SmsMessageReportStatus::QUEUED,
    'ACKED'   => SmsMessageReportStatus::QUEUED,
    'ENROUTE' => SmsMessageReportStatus::QUEUED,
    'DELETED' => SmsMessageReportStatus::EXPIRED,
    'ACCEPTED' => SmsMessageReportStatus::QUEUED,
  ];

}
