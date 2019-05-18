<?php

namespace Drupal\commerce_wayforpay\Form;

use Drupal\commerce_wayforpay\Helpers\Arr;
use Drupal\commerce_wayforpay\Helpers\Validation;

/**
 * Trait WayforpayFormTrait.
 *
 * @package Drupal\commerce_wayforpay\Form
 */
trait WayforpayFormTrait {

  /**
   * Gateway configuration.
   *
   * @var array
   */
  private $config;

  /**
   * Get wayforpay error.
   *
   * @param string $error_code
   *   Error code.
   *
   * @return array
   *   Error info.
   */
  public static function getErrorInfo($error_code) {
    $error_messages = [
      '1100' => [
        'name'               => 'OK',
        'reason'             => t('the operation was performed without errors'),
        'message_for_client' => '',
        'where_to_go'        => '',
      ],
      '1101' => [
        'name'               => t('Declined to Card Issuer'),
        'reason'             => t("the Bank's referral of the Issuer to carry out the operation"),
        'message_for_client' => t('Failed to make payment.Contact your Bank or use another card'),
        'where_to_go'        => t('The Bank card Issuer'),
      ],
      '1102' => [
        'name'               => t('CVV2 Bad'),
        'reason'             => t('Invalid CVV code'),
        'message_for_client' => t('Failed to make payment. Please make sure you enter the correct parameters and try again'),
        'where_to_go'        => t('The Bank card Issuer'),
      ],
      '1103' => [
        'name'               => t('Expired card'),
        'reason'             => t('Card expired or expiration date incorrect'),
        'message_for_client' => t('Payment failed. Contact your Bank or use another card. Please make sure you enter the correct parameters and try again'),
        'where_to_go'        => t('card Issuer Bank'),
      ],
      '1104' => [
        'name'               => t('Insufficient Funds'),
        'reason'             => t('insufficient funds'),
        'message_for_client' => t('payment failed. Insufficient funds on the card'),
        'where_to_go'        => t('card Issuer Bank'),
      ],
      '1105' => [
        'name'               => t('Invalid Card'),
        'reason'             => t('an incorrect card number has been Entered or the card is in an invalid state.'),
        'message_for_client' => t('payment failed.Contact your Bank or use another card.Please make sure you enter the correct parameters and try again'),
        'where_to_go'        => t('card Issuer Bank'),
      ],
      '1106' => [
        'name'               => t('Exceed withdraw Frequency'),
        'reason'             => t('you have Exceeded the limit of operations on the map - perhaps the map is not open to pay for the Internet'),
        'message_for_client' => t('payment failed.Contact your Bank or use another card'),
        'where_to_go'        => t('card Issuer Bank'),
      ],
      '1108' => [
        'name'               => t('Three Ds Fail'),
        'reason'             => t('unable to execute 3ds transaction or invalid 3ds verification code'),
        'message_for_client' => t('Contact your Bank or use another card'),
        'where_to_go'        => t('card Issuer Bank'),
      ],
      '1109' => [
        'name'               => t('Format Error'),
        'reason'             => t('Error on the side of the merchant — malformed transaction'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1110' => [
        'name'               => t('invalid Currency'),
        'reason'             => t('Error on the side of the merchant - wrong currency'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1112' => [
        'name'               => t('Duplicate Order ID'),
        'reason'             => t('duplicate orderid'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1113' => [
        'name'               => t('Invalid signature.'),
        'reason'             => t('not the correct signature of the merchant.'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1114' => [
        'name'               => t('Fraud'),
        'reason'             => t('Fraud transaction according to anti-fraud filters'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1115' => [
        'name'               => t('Parameter `array(param_name)` is missing'),
        'reason'             => t('One or more required parameters are not passed'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1116' => [
        'name'               => t('Token not found'),
        'reason'             => t('Attempted cancellation of the card client token failure - used wrong value'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('trader'),
      ],
      '1117' => [
        'name'               => t('API Not Allowed'),
        'reason'             => t('This API is not permitted for merchant use'),
        'message_for_client' => t('Payment failed. Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('psp'),
      ],
      '1118' => [
        'name'               => t('Merchant Restriction'),
        'reason'             => t('Store limit Exceeded or transactions banned'),
        'message_for_client' => t('Payment failed. Please try again later or contact the merchant you are making the payment to.'),
        'where_to_go'        => t('psp'),
      ],
      '1120' => [
        'name'               => t('Authentication unavailable'),
        'reason'             => t('3-D Secure authorization is not available'),
        'message_for_client' => t('Payment failed. Contact your Bank or use another card'),
        'where_to_go'        => t('psp'),
      ],
      '1121' => [
        'name'               => t('Account not Found'),
        'reason'             => t('Account not found'),
        'message_for_client' => t('Payment failed. Contact the merchant whose address you pay'),
        'where_to_go'        => t('psp'),
      ],
      '1122' => [
        'name'               => t('Gate declined'),
        'reason'             => t('Gateway Failure in operation'),
        'message_for_client' => t('Payment failed. Please try again later. If the message is displayed again contact us, we will try to help you.'),
        'where_to_go'        => t('psp'),
      ],
      '1123' => [
        'name'               => t('Refund Not Allowed'),
        'reason'             => t('Refund cannot be made'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1124' => [
        'name'               => t('Cardholder session expired'),
        'reason'             => t('User session expired'),
        'message_for_client' => t('The time to make a payment has expired, please try again.'),
        'where_to_go'        => t('psp'),
      ],
      '1125' => [
        'name'               => t('Cardholder canceled the request'),
        'reason'             => t('Transaction cancelled by user'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1126' => [
        'name'               => t('Illegal Order State'),
        'reason'             => t('Attempt to execute an invalid operation for the current state of payment'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1127' => [
        'name'               => t('Order Not Found'),
        'reason'             => t('transaction not found'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1128' => [
        'name'               => t('Refund Limit Exceeded'),
        'reason'             => t('you have Exceeded the allowable number of attempts to process a return (Refund)'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1129' => [
        'name'               => t('ScriptError'),
        'reason'             => t('script error'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('psp'),
      ],
      '1130' => [
        'name'               => t('Invalid Amount'),
        'reason'             => t('incorrect amount'),
        'message_for_client' => t('payment failed.Check the correctness of the transferred amount in the request.'),
        'where_to_go'        => t('psp'),
      ],
      '1131' => [
        'name'               => t('Transaction in processing'),
        'reason'             => t('The Order is processed. The order is still being processed by the payment gateway'),
        'message_for_client' => t('Your payment is processed. As soon as the transaction is processed, You will receive a notification on payment status'),
        'where_to_go'        => t('pspBank card Issuer'),
      ],
      '1132' => [
        'name'               => t('Transaction Is Delayed'),
        'reason'             => t('The Customer has decided to postpone the payment, a link to complete the payment has been sent to his e-mail'),
        'message_for_client' => t('Your order is created .. on the website ... you can Make a payment within XX hours XX minutes.'),
        'where_to_go'        => '',
      ],
      '1133' => [
        'name'               => t('Invalid commission'),
        'reason'             => t('incorrect Commission.'),
        'message_for_client' => t('no'),
        'where_to_go'        => t('trader'),
      ],
      '1134' => [
        'name'               => t('Transaction is pending'),
        'reason'             => t('Antifraud verification Transaction'),
        'message_for_client' => t('Transaction is on manual check of monitoring employee.'),
        'where_to_go'        => t('psp'),
      ],
      '1135' => [
        'name'               => t('Card limits failed'),
        'reason'             => t('you have Exceeded the limit on the card'),
        'message_for_client' => t('payment failed.Please try again later or contact the merchant whose address you are paying for'),
        'where_to_go'        => t('psp'),
      ],
      '1136' => [
        'name'               => t('Merchant Balance Is Very Small'),
        'reason'             => t('insufficient funds on merchant`s balance'),
        'message_for_client' => '',
        'where_to_go'        => t('psp'),
      ],
      '1137' => [
        'name'               => t('Invalid Confirmation Amount'),
        'reason'             => t('incorrect card verification confirmation amount'),
        'message_for_client' => t('card Verification failed'),
        'where_to_go'        => '',
      ],
      '1138' => [
        'name'               => t('RefundInProcessing'),
        'reason'             => t('The refund Request is accepted and will be made as soon as there is enough money on the store balance sheet to carry it out.'),
        'message_for_client' => '',
        'where_to_go'        => t('psp'),
      ],
      '1139' => [
        'name'               => t('External decline while credit'),
        'reason'             => t('refusal to transfer funds to the recipient`s card'),
        'message_for_client' => t('Refusal to transfer funds to the recipient`s card'),
        'where_to_go'        => t('NRBank card Issuer'),
      ],
      '1140' => [
        'name'               => t('Exceed Withdrawal Frequency While Credit'),
        'reason'             => t('the limit is Exceeded when depositing funds to the recipient`s card.'),
        'message_for_client' => t('Limit Exceeded when crediting funds to the card recipient.'),
        'where_to_go'        => t('NRBank card Issuer'),
      ],
      '1141' => [
        'name'               => t('Partial void is not supported'),
        'reason'             => t('the Partial lifting of the hold is not available'),
        'message_for_client' => t('the Partial lifting of the hold is not available'),
        'where_to_go'        => t('psp'),
      ],
      '1142' => [
        'name'               => t('Revised a credit'),
        'reason'             => t('credit Denied'),
        'message_for_client' => t('payment failed.'),
        'where_to_go'        => t('psp'),
      ],
      '1143' => [
        'name'               => t('Invalid phone number'),
        'reason'             => t('invalid phone number'),
        'message_for_client' => t('Invalid phone number'),
        'where_to_go'        => '',
      ],
      '1144' => [
        'name'               => t('Transaction is await delivery'),
        'reason'             => '',
        'message_for_client' => '',
        'where_to_go'        => t('psp'),
      ],
      '1145' => [
        'name'               => t('Transaction is await credit decision'),
        'reason'             => t('Waiting for loan decision'),
        'message_for_client' => '',
        'where_to_go'        => t('psp'),
      ],
      '5100' => [
        'name'               => t('Wait 3ds data'),
        'reason'             => t('waiting for 3d secure verification'),
        'message_for_client' => '',
        'where_to_go'        => '',
      ],
    ];
    if (isset($error_messages[$error_code])) {
      return $error_messages[$error_code];
    }
    return [
      'name'               => '',
      'reason'             => '',
      'message_for_client' => t('Failed to make payment. Contact us via the feedback form'),
      'where_to_go'        => t('merchant'),
    ];
  }

  /**
   * Returns Signature.
   *
   * @param array $cleaned_data
   *   Cleaned data.
   *
   * @return string
   *   Signature.
   */
  public function makeSignature(array $cleaned_data) {
    $data      = [
      Arr::get($cleaned_data, 'merchantAccount', NULL),
      Arr::get($cleaned_data, 'partnerCode', NULL),
      Arr::get($cleaned_data, 'phone', NULL),
      Arr::get($cleaned_data, 'email', NULL),
      Arr::get($cleaned_data, 'merchantDomainName', NULL),
      Arr::get($cleaned_data, 'orderReference', NULL),
      Arr::get($cleaned_data, 'orderDate', NULL),
      Arr::get($cleaned_data, 'status', NULL),
      Arr::get($cleaned_data, 'time', NULL),
      Arr::get($cleaned_data, 'amount', NULL),
      Arr::get($cleaned_data, 'currency', NULL),
      Arr::get($cleaned_data, 'productName', NULL),
      Arr::get($cleaned_data, 'productCount', NULL),
      Arr::get($cleaned_data, 'productPrice', NULL),
      Arr::get($cleaned_data, 'authCode', NULL),
      Arr::get($cleaned_data, 'cardPan', NULL),
      Arr::get($cleaned_data, 'transactionStatus', NULL),
      Arr::get($cleaned_data, 'reasonCode', NULL),
    ];
    $sign_data = [];
    foreach ($data as $i) {
      if (is_null($i)) {
        continue;
      }
      elseif (is_array($i)) {
        foreach ($i as $i2) {
          $sign_data[] = (string) $i2;
        }
      }
      else {
        $sign_data[] = (string) $i;
      }
    }

    $sign_data_string = implode(';', $sign_data);
    return hash_hmac("md5", $sign_data_string, $this->config['secretKey']);
  }

  /**
   * Wayforpay Confirm translation.
   *
   * @param array $cleaned_data
   *   Cleaned data.
   *
   * @return array
   *   Result.
   */
  public function wayforpayConfirmTransaction(array $cleaned_data) {
    $data                      = [
      'transactionType' => 'SETTLE',
      'merchantAccount' => $cleaned_data['merchantAccount'],
      'orderReference'  => $cleaned_data['orderReference'],
      'amount'          => $cleaned_data['amount'],
      'currency'        => $cleaned_data['currency'],
      'apiVersion'      => 1,
    ];
    $data['merchantSignature'] = $this->makeSignature($data);
    $data                      = array_merge($data, [
      'partnerCode'  => Arr::get($cleaned_data, 'partnerCode',
        [$this->config['merchantAccount']]),
      'partnerPrice' => Arr::get($cleaned_data, 'partnerPrice',
        [$cleaned_data['amount']]),
    ]);
    return $this->api('https://api.wayforpay.com/api', $data);
  }

  /**
   * Api call.
   *
   * @param string $method
   *   Method name.
   * @param array $data
   *   Param.
   *
   * @return array
   *   Result.
   *
   * @throws \Exception
   */
  public function api($method, array $data) {
    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger       = \Drupal::logger('commerce_wayforpay');
    $request_data = [];
    foreach ($data as $k => $v) {
      if ($v) {
        $request_data[$k] = $v;
      }
    }
    $request_data_string         = json_encode($request_data);
    $headers                     = [
      'Content-Type'   => 'application/json',
      'Content-Length' => strlen($request_data_string),
    ];
    $options[CURLOPT_HTTPHEADER] = [];
    foreach ($headers as $key => $value) {
      $options[CURLOPT_HTTPHEADER][] = $key . ': ' . $value;
    }
    $options[CURLOPT_SSL_VERIFYPEER] = TRUE;
    $options[CURLOPT_SSL_VERIFYHOST] = 2;
    $options[CURLOPT_CUSTOMREQUEST]  = 'POST';
    $options[CURLOPT_POSTFIELDS]     = $request_data_string;
    $options[CURLOPT_RETURNTRANSFER] = TRUE;
    $curl                            = curl_init($method);
    curl_setopt_array($curl, $options);
    $body = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    // Close the connection.
    curl_close($curl);
    $logger->debug($body);
    $resp_json = json_decode($body, TRUE);
    if (is_null($resp_json)) {
      $error_code = 'unknown';
      $mss        = strtr('Error method {0} error: {1} code: {2} http_status: {3}',
        [
          '{0}' => $method,
          '{1}' => $body,
          '{2}' => $error_code,
          '{3}' => $code,
        ]);
      $logger->error($mss);
      throw new \Exception($mss);
    }
    $error_message = '';
    $error_code    = NULL;
    if (isset($resp_json['reasonCode']) == FALSE) {
      $error_message = $body;
      $error_code    = 'unknown';
    }
    elseif ((int) $resp_json['reasonCode'] != 1100) {
      $error_message = $resp_json['reason'];
      $error_code    = $resp_json['reasonCode'];
    }
    if (is_null($error_code) === FALSE) {
      $mss = strtr('Error method {0} error: {1} code: {2}', [
        '{0}' => $method,
        '{1}' => $error_message,
        '{2}' => $error_code,
      ]);
      $logger->error($mss);
      throw new \Exception($mss);
    }
    return $resp_json;
  }

  /**
   * Cancel transaction.
   *
   * @param array $cleaned_data
   *   Cleaned data.
   * @param string $comment
   *   Cancel reason.
   *
   * @return array
   *   Result
   */
  public function wayforpayCancelTransaction(array $cleaned_data, $comment) {
    $data                      = [
      'transactionType' => 'REFUND',
      'merchantAccount' => $cleaned_data['merchantAccount'],
      'orderReference'  => $cleaned_data['orderReference'],
      'amount'          => $cleaned_data['amount'],
      'currency'        => $cleaned_data['currency'],
      'comment'         => $comment,
      'apiVersion'      => 1,
    ];
    $data['merchantSignature'] = $this->makeSignature($data);
    $data                      = array_merge($data, [
      'partnerCode'  => Arr::get($cleaned_data, 'partnerCode'),
      'partnerPrice' => Arr::get($cleaned_data, 'partnerPrice'),
    ]);
    return $this->api('https://api.wayforpay.com/api', $data);
  }

  /**
   * Validation Payment.
   *
   * @param array $data
   *   Form data.
   *
   * @return bool
   *   Validation result.
   *
   * @throws \Exception
   */
  private function validatePaymentForm(array $data) {
    $merchant_data       = $data;
    $validation_settings = Validation::factory($this->config)
      ->rule('secretKey', 'not_empty');
    $validation_merchant = Validation::factory($merchant_data)
      ->rule('merchantAccount', 'not_empty')
      ->rule('merchantAuthType', 'not_empty')
      ->rule('merchantDomainName', 'not_empty')
      ->rule('merchantTransactionType',
                                       'not_empty')
      ->rule('merchantTransactionSecureType',
                                       'not_empty')
      ->rule('language', 'not_empty')
      ->rule('returnUrl', 'not_empty')
      ->rule('serviceUrl', 'not_empty')
      ->rule('amount', 'not_empty')
      ->rule('currency', 'not_empty')
      ->rule('productName', 'not_empty')
      ->rule('productPrice', 'not_empty')
      ->rule('productCount', 'not_empty')
      ->rule('apiVersion', 'not_empty')
      ->rule('holdTimeout', 'not_empty')
      ->rule('orderLifetime', 'not_empty');
    if (!$validation_settings->check() or !$validation_merchant->check()) {
      throw new \Exception('Incorrect configuration' . '<br />' . implode('<br />',
          $validation_merchant->errors) . '<br />' . implode('<br />',
          $validation_settings->errors));
    }
    return TRUE;
  }

}
