<?php

namespace Drupal\publishthis\Classes;

use Drupal\publishthis\Classes\common\Publishthis_API_Common;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Publishthis_API.
 */
class Publishthis_API extends Publishthis_API_Common {

  /**
   * Publishthis_API constructor.
   */
  function __construct() {
    $this->_api_url = PUBLISHTHIS_PT_API_URL;
  }

  /**
   * Return token saved value.
   */

  public function GetToken() {
    $config = \Drupal::config('publishthis.settings');
    $saved_token = $config->get('pt_api_token');
    return $saved_token;
  }

  /**
   * Set dblogs for PublishThis.
   */
  public function LogMessage($message, $level = '') {
    if (!is_array($message)) {
      $message = ['message' => $message, 'status' => 'info'];
    }

    switch ($message['status']) {
      case 'warn':
        $severity = RfcLogLevel::WARNING;
        break;

      case 'error':
        $severity = RfcLogLevel::ERROR;
        break;

      case 'info':
      default:
        $severity = RfcLogLevel::INFO;
        break;
    }
    \Drupal::logger('publishthis')->log($severity, $message['message']);
  }

  /**
   * Returns Publishthis client info.
   */
  public function get_client_info($params = []) {

    $params = $params + ['token' => $this->GetToken()];

    $url = $this->_compose_api_call_url('/client', $params);

    try {
      $response = $this->_request($url);
    }
    catch (Exception $ex) {
      $this->LogMessage($ex->getMessage(), '7');
      $response = NULL;
    }

    return $response;
  }

  /**
   * Process API request.
   */
  public function _request($url, $return_errors = FALSE) {
    // Check token setup.
    $query_str = parse_url($url, PHP_URL_QUERY);
    parse_str($query_str, $query_params);
    if (empty($query_params['token'])) {
      return NULL;
    }

    // Process request.
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    $data = curl_exec($ch);

    // Check HTTP Code.
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL Resource.
    curl_close($ch);

    // Check for failure.
    if (!isset($data) || $status != 200) {
      $message = [
        'message' => 'PublishThis API error URL ' . $url,
        'status' => 'error',
      ];
      $this->LogMessage($message, '2');
    }

    $json = '';
    try {
      $json = json_decode($data);

      if (!$json) {
        $message = [
          'message' => 'Inner JSON conversion error URL ' . $url,
          'status' => 'error',
        ];
        $this->LogMessage($message, '2');
      }

    }
    catch (Exception $ex) {
      // Try utf encoding it and then capturing it again.
      // We have seen problems in some wordpress/server installs where the
      // json_decode doesn't actually like the utf-8 response that is returned.
      $message = [
        'message' => 'Issue in decoding the json ' . $ex->getMessage(),
        'status' => 'error',
      ];
      $this->LogMessage($message, '2');

      try {
        $tmpBody = utf8_encode($data);
        $json = json_decode($tmpBody);
      }
      catch (Exception $exc) {
        $message = [
          'message' => 'Issue in utf8 encoding and then decoding the json ' . $ex->getMessage(),
          'status' => 'error',
        ];
        $this->LogMessage($message, '2');
      }
    }

    if (!$json) {
      $message = [
        'message' => 'JSON conversion error URL ' . $url,
        'status' => 'error',
      ];
      $this->LogMessage($message, '2');
    }

    return $status == 200 ? $json->resp->data : NULL;

  }

  // Get feed templates.
  
  public function get_feed_templates($params = []) {
    $params = $params + ['token' => $this->GetToken()];

    $url = $this->_compose_api_call_url('/publishtypes/posts', $params);

    try {
      $response = $this->_request($url);
      $templates = [];
      if ($response->totalAvailable > 0) {
        foreach ($response->items as $item) {
          $templates[$item->templateId] = $item->displayName;
        }
      }
      return $templates;
    }
    catch (Exception $ex) {
      $this->LogMessage($ex->getMessage());
    }
  }

}
