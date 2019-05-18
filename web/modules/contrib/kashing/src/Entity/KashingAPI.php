<?php

namespace Drupal\kashing\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

/**
 * Kashing API class.
 */
class KashingAPI {

  private $mode;

  private $merchantID;

  private $secretKey;

  private $currency;

  private $apiURL;

  private $hasErrors;

  private $errors;

  private $config;

  private $redirectURL;

  private $responseMessage;

  /**
   * Construct.
   */
  public function __construct() {

    $this->hasErrors = FALSE;
    $this->errors = [];
    $this->config = \Drupal::config('kashing.settings');

    if (!isset($this->config)) {
      $this->addError([
        'field' => 'settings',
        'type' => 'missing_data',
        'msg' => t('The kashing settings data is missing.'),
      ]);
    }
    else {
      $this->initConfiguration();
    }

  }

  /**
   * Initial configuration.
   */
  private function initConfiguration() {

    $this->mode = $this->config->get('mode');

    // Determine api mode (test default)
    if ($this->mode == 'live') {
      $option_mode = 'live';
      $this->apiURL = 'https://api.kashing.co.uk/';
    }
    else {
      $option_mode = 'test';
      $this->apiURL = 'https://development-backend.kashing.co.uk/';
    }

    // Merchant ID.
    $merchantID = $this->config->get('key')[$option_mode]['merchant'];

    if (isset($merchantID)) {
      $this->merchantID = $merchantID;
    }
    else {
      $this->addError([
        'field' => 'merchantID',
        'type' => 'missing_field',
        'msg' => t('The merchant ID is missing.'),
      ]);
    }

    // Secret Key.
    $secretKey = $this->config->get('key')[$option_mode]['secret'];

    if (isset($secretKey)) {
      $this->secretKey = $secretKey;
    }
    else {
      $this->addError([
        'field' => 'secret_key',
        'type' => 'missing_field',
        'msg' => t('The secret key is missing.'),
      ]);
    }

    // Currency
    // default set to GBP.
    $currency = $this->config->get('currency') ? $this->config->get('currency') : 'GBP';

    if (isset($currency)) {
      $this->currency = $currency;
    }
    else {
      $this->addError([
        'field' => 'currency',
        'type' => 'missing_field',
        'msg' => t('The currency ISO code is missing.'),
      ]);
    }

    if ($this->hasErrors === FALSE) {
      // Configuration is successful.
      return TRUE;
    }

    // There are errors in the plugin configuration.
    return FALSE;
  }

  /**
   * Add error function.
   */
  private function addError($error) {

    if ($this->hasErrors == FALSE) {
      $this->hasErrors = TRUE;
    }

    if (is_array($error)) {
      $this->errors[] = $error;
      return TRUE;
    }

    return FALSE;
  }

  /**
   * If has eny errors.
   */
  public function hasErrors() {
    return $this->hasErrors;
  }

  /**
   * Get all errors.
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Process transaction.
   */
  public function process($transaction_data) {

    $url = $this->apiURL . 'transaction/init';

    // Return URL
    // currently returning to previous page.
    $return_url = $this->getCurrentUrl();
    $transaction_data['returnurl'] = $return_url;

    $transaction_data['merchantid'] = $this->merchantID;
    $transaction_data['currency'] = $this->currency;

    $data_string = '';

    foreach ($transaction_data as $data_value) {
      $data_string .= $data_value;
    }

    $transaction_psign = $this->getPsign($transaction_data);

    $final_transaction_array = [
      'transactions' => [
        array_merge(
                $transaction_data,
                [
                  'psign' => $transaction_psign,
                ]
        ),
      ],
    ];

    $body = json_encode($final_transaction_array);

    $request = \Drupal::httpClient()->post($url, [
      'method' => 'POST',
      'body' => $body,
      'timeout' => 10,
      'headers' => [
        'Content-type' => 'application/json',
      ],
    ])->getBody()->getContents();

    $response_body = json_decode($request);

    if (isset($response_body->error) && isset($response_body->responsecode)) {

      if ($response_body->responsecode == 1 && isset($response_body->results)
        && isset($response_body->results[0]) && isset($response_body->results[0]->responsecode)
        && isset($response_body->results[0]->reasoncode)) {

        // Redirection.
        if ($response_body->results[0]->responsecode == 4 && $response_body->results[0]->reasoncode == 1
        && isset($response_body->results) && isset($response_body->results[0]->redirect)) {

          // Redirecting the user
          // Kashing redirect URL.
          $this->redirectURL = $response_body->results[0]->redirect;

          return TRUE;

        }
        // No Redirect URL.
        else {
          $this->addError([
            'field' => 'process',
            'type' => 'response',
            'msg' => t('There was something wrong with a redirection response from the Kashing server.'),
          ]);

          return FALSE;
        }
      }

      // An error.
      $this->responseMessage = [];
      $this->responseMessage['response_code'] = $response_body->responsecode;
      $this->responseMessage['reason_code'] = $response_body->reasoncode;
      $this->responseMessage['error'] = $response_body->error;

      // Additional suggestion based on the error type.
      $suggestion = $this->getApiErrorSuggestion($response_body->responsecode, $response_body->reasoncode);

      if ($suggestion != FALSE) {
        $this->responseMessage['suggestion'] = $suggestion;
      }

      return FALSE;
    }

    return FALSE;
  }

  /**
   * Get redirect Url.
   */
  public function getRedirectUrl() {
    return $this->redirectURL;
  }

  /**
   * Get any response Messages.
   */
  public function getResponseMessage() {
    return $this->responseMessage;
  }

  /**
   * Get API error suggestion.
   */
  private function getApiErrorSuggestion($response_code, $reason_code) {

    if ($response_code == 3) {
      switch ($reason_code) {
        case 9:
          return t('Please make sure your Merchant ID is correct.');

        case 104:
          return t('Please make sure that your Secret API Key and Merchant ID are correct.');
      }
    }

    return '';
  }

  /**
   * Get current URL.
   */
  private function getCurrentUrl() {

    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_alias = \Drupal::service('path.alias_manager')->getAliasByPath($current_path);
    $url = Url::fromUserInput($current_path_alias, ['absolute' => TRUE])->toString();

    return $url;
  }

  /**
   * Get PSign.
   */
  private function getPsign($data_array) {

    $data_string = $this->secretKey . $this->extractTransactionData($data_array);

    $psign = sha1($data_string);

    return $psign;
  }

  /**
   * Extract Transaction Data.
   */
  private function extractTransactionData($data_array) {

    $data_string = '';
    foreach ($data_array as $data_value) {
      $data_string .= $data_value;
    }

    return $data_string;
  }

  /**
   * Get transaction error details.
   */
  public function apiGetTransactionErrorDetails($transaction_id, $uid = NULL) {

    // Full API Call URL.
    $url = $this->apiURL . 'transaction/find';

    // Call data array.
    $data_array = [
      'token' => $transaction_id,
    ];

    // Psign.
    $call_psign = $this->getPsign($data_array);

    // Final API Call Body with the psign (merging with the $transaction_data)
    $final_data_array = array_merge(
        $data_array,
        [
          'psign' => $call_psign,
        ]
    );

    // Encode the final transaction array to JSON.
    $body = json_encode($final_data_array);

    // Make the API Call.
    $request = \Drupal::httpClient()->post($url, [
      'method' => 'POST',
      'body' => $body,
      'timeout' => 10,
      'headers' => [
        'Content-type' => 'application/json',
      ],
    ])->getBody()->getContents();

    // Deal with the API response.
    $response_body = json_decode($request);

    $return = [
      'transactionID' => $transaction_id,
    ];

    // The gateway message.
    if (isset($response_body->gatewaymessage)) {
      if ($response_body->gatewaymessage == '') {
        $return["gatewaymessage"] = t('No additional gateway message provided.');
        $return["nogateway"] = TRUE;
      }
      else {
        $return["gatewaymessage"] = Html::escape($response_body->gatewaymessage);
      }
    }

    // The reason and response codes.
    if (isset($response_body->responsecode)) {
      $return["responsecode"] = Html::escape($response_body->responsecode);
    }

    if (isset($response_body->reasoncode)) {
      $return["reasoncode"] = Html::escape($response_body->reasoncode);
    }

    // Return the array.
    return $return;
  }

}
