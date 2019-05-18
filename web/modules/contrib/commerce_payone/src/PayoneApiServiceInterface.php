<?php

namespace Drupal\commerce_payone;

/**
 * Interface PayoneClientApiInterface.
 *
 * @package Drupal\commerce_payone
 */
interface PayoneApiServiceInterface {

  /**
   * With each Client API request the following parameters must always be submitted.
   * @see 3.1.2 Standard parameter (Technical Reference for Client API)
   *
   * @param array $plugin_configuration
   * @param string $request_method
   * @param string $response_type
   * @return array
   */
  public function getClientApiStandardParameters(array $plugin_configuration, $request_method, $response_type = 'JSON');

  /**
   * With each Server API request the following parameters must always be submitted.
   * @see 3.1.2 Standard parameter (Technical Reference for Client API)
   *
   * @param array $plugin_configuration
   * @param string $request_method
   * @return array
   */
  public function getServerApiStandardParameters(array $plugin_configuration, $request_method);

  /**
   * Calculates the hash value required in Client API requests.
   *
   * @param array $data
   * @param string $securitykey
   * @return string
   */
  public function generateHash(array $data, $securitykey);

  /**
   * Processes HTTPS-POST requests (key/value pairs) between this system and PAYONE Platform.
   *
   * @param array $form_parameters
   * @param bool $client_api
   * @return mixed
   */
  public function processHttpPost(array $form_parameters, $client_api = TRUE);
}
