<?php

namespace Drupal\omeda;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\encryption\EncryptionService;
use Psr\Log\LoggerInterface;

/**
 * Establishes a connection to Omeda.
 */
class Omeda {

  use StringTranslationTrait;

  /**
   * The omeda.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs an Omeda object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption, LoggerInterface $logger, TranslationInterface $string_translation) {
    $this->config = $config_factory->get('omeda.settings');
    $this->encryption = $encryption;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Retrieve general brand information.
   *
   * @see https://jira.omeda.com/wiki/en/Brand_Comprehensive_Lookup_Service
   *
   * @return array
   *   Omeda api response
   *
   * @throws \Exception
   */
  public function brandComprehensiveLookup() {
    $api_response = $this->submitRequest('/comp/*', 'GET');

    if (isset($api_response['Errors'][0]['Error'])) {
      $this->handleApiError($api_response['Errors'][0]['Error']);
    }

    return $api_response;
  }

  /**
   * Submit a request to the Omeda API.
   *
   * @see https://jira.omeda.com/wiki/en/API_Suite
   *
   * @param string $path
   *   The path of the API endpoint to request. This should only be the part of
   *   the URL after the brand abbreviation, and may optionally include a slash
   *   at the beginning.
   * @param string $method
   *   An HTTP method, one of "GET", "POST", "PUT", "DELETE", or "HEAD".
   * @param array|object $data
   *   Data to submit to the Omeda API.
   * @param bool $include_input_id
   *   Whether or not to include the configured Omeda Input ID in the request.
   * @param bool $use_client_abbreviation_in_url
   *   By default, API calls include the brand abbreviation in the URL. However,
   *   some calls require the use of the client abbreviation instead. Pass TRUE
   *   to use the client abbreviation.
   *
   * @return array
   *   Cleaned Omeda api response
   *
   * @throws \Exception
   */
  public function submitRequest($path, $method, $data = [], $include_input_id = FALSE, $use_client_abbreviation_in_url = FALSE) {

    $api_mode = $this->config->get('api_mode');
    if (!in_array($api_mode, ['production', 'testing'])) {
      $this->handleError('Could not submit request. The Omeda API mode has not been set to a valid value of either "production" or "testing".');
    }

    $base_url = $this->encryption->decrypt($this->config->get($api_mode . '_api_url'), TRUE);
    if (!$base_url) {
      $this->handleError('Could not submit request. No Omeda base URL is configured for the current mode.');
    }

    $abbreviation = NULL;
    if ($use_client_abbreviation_in_url) {
      $abbreviation = $this->encryption->decrypt($this->config->get('client_abbreviation'), TRUE);
      if (!$abbreviation) {
        $this->handleError('Could not submit request. No client abbreviation is configured for Omeda.');
      }
    }
    else {
      $abbreviation = $this->encryption->decrypt($this->config->get('brand_abbreviation'), TRUE);
      if (!$abbreviation) {
        $this->handleError('Could not submit request. No brand abbreviation is configured for Omeda.');
      }
    }

    $abbreviation_prefix = $use_client_abbreviation_in_url ? 'client/' : 'brand/';

    $app_id = $this->encryption->decrypt($this->config->get('app_id'), TRUE);
    if (!$app_id) {
      $this->handleError('Could not submit request. No App ID is configured for Omeda.');
    }

    if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'])) {
      $this->handleError('Could not submit request. The supplied method is unsupported.');
    }

    // The leading/trailing slashes in the config and params are optional.
    $normalized_base_url = $base_url . (substr($base_url, -1, 1) !== '/' ? '/' : '');
    $normalized_path = (substr($path, 0, 1) !== '/' ? '/' : '') . $path;
    $url = $normalized_base_url . $abbreviation_prefix . $abbreviation . $normalized_path;

    $headers = [
      'x-omeda-appid: ' . $app_id,
    ];

    $request_data = $data ? json_encode($data) : NULL;

    if ($method === 'POST') {
      $headers[] = 'Content-Type: application/json';
      if ($request_data) {
        $headers[] = 'Content-Length: ' . strlen($request_data);
      }
    }

    if ($include_input_id) {
      $input_id = $this->encryption->decrypt($this->config->get('input_id'), TRUE);
      if (!$input_id) {
        $this->handleError('Could not submit request. No Input ID is configured for Omeda, and the call requires that there be one.');
      }
      $headers[] = 'x-omeda-inputid: ' . $input_id;
    }

    // Build the API request.
    $request = curl_init($url);
    curl_setopt($request, CURLOPT_CUSTOMREQUEST, $method);
    if ($request_data) {
      curl_setopt($request, CURLOPT_POSTFIELDS, $request_data);
    }
    curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($request, CURLOPT_HTTPHEADER, $headers);

    // Submit the request to the API.
    $response_message = curl_exec($request);
    if (!$response_message) {
      $this->handleError('No response was received from the Omeda API.');
    }

    // Parse the response so that we can process it.
    $response = json_decode($response_message, TRUE);

    if (!$response) {
      $this->handleError('The response received from the Omeda API was not valid JSON.');
    }

    return $response;
  }

  /**
   * Logs an error and throws an exception.
   *
   * @param string $message
   *   The error message.
   * @param array $context
   *   Any parameters needed in order to build the error message.
   *
   * @throws \Exception
   */
  private function handleError($message, array $context = []) {
    $this->logger->error($message, $context);
    throw new \Exception($this->t($message, $context));
  }

  /**
   * Logs an error from the Omeda API and throws an exception.
   *
   * This is intended to be used when the Omeda API response is being parsed and
   * it is determined to be in an error state.
   *
   * @param string $message
   *   The error message.
   *
   * @throws \Exception
   */
  public function handleApiError($message) {
    $this->handleError('An unexpected error response was received from the Omeda API: @error', [
      '@error' => $message,
    ]);
  }

}
