<?php

namespace Drupal\sms_infobip\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;

/**
 * Normalizes XML send responses to the SmsMessageResult object.
 */
class MessageResponseHandler extends InfobipResponseHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function handle($body) {
    $response = Json::decode($body);
    if ($response['messages']) {
      $result = (new SmsMessageResult())
        ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'));
      $reports = [];
      foreach ($response['messages'] as $message) {
        $report = (new SmsDeliveryReport())
          ->setRecipient($message['to'])
          ->setStatus($this->mapStatus($message['status']))
          ->setMessageId($message['messageId'])
          ->setStatusMessage($message['status']['description'])
          ->setStatusTime(REQUEST_TIME);
        if (isset($message['error'])) {
          $report->setStatus($this->mapError($message['error']));
        }
        $reports[$message['to']] = $report;
      }
      $result->setReports($reports);
    }
    else {
      // @todo should we check the HTTP response code?
      $result = (new SmsMessageResult())
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage(new TranslatableMarkup('Unknown SMS Gateway error'));
    }
    return $result;
  }

}
