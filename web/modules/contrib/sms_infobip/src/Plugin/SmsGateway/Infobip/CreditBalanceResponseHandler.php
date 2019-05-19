<?php

namespace Drupal\sms_infobip\Plugin\SmsGateway\Infobip;

use Drupal\Component\Serialization\Json;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultStatus;

/**
 * Normalizes the credit balance response to an SmsMessageResult object.
 */
class CreditBalanceResponseHandler extends InfobipResponseHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function handle($body) {
    $response = Json::decode($body);
    $result = new SmsMessageResult();
    if (isset($response['balance'])) {
      $result->setCreditsBalance(floatval($response['balance']));
//      $message = $response['currency'] . ' ' . $response['balance'];
    }
    else {
      $result
        ->setError(SmsMessageResultStatus::ERROR)
        ->setErrorMessage(t('Not available'));
    }
    return $result;
  }

}
