<?php

namespace Drupal\commerce_payone;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\ClientInterface;

/**
 * Class PaymentManager.
 *
 * @package Drupal\commerce_payone
 */
class PayoneApiService implements PayoneApiServiceInterface {
  const CLIENT_API_URL = 'https://secure.pay1.de/client-api/';
  const SERVER_API_URL = 'https://api.pay1.de/post-gateway/';

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\Client
   */
  protected $http_client;

  /**
   * PayoneClientApi constructor.
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->http_client = $http_client;
  }

  /**
   * With each Client API request the following parameters must always be submitted.
   * @see 3.1.2 Standard parameter (Technical Reference for Client API)
   *
   * @param array $plugin_configuration
   * @param string $request_method
   * @param string $response_type
   * @return array
   */
  public function getClientApiStandardParameters(array $plugin_configuration, $request_method, $response_type = 'JSON') {
    $parameters = [
      'mid' => $plugin_configuration['merchant_id'],
      'portalid' => $plugin_configuration['portal_id'],
      'api_version' => '3.10',
      'mode' => $plugin_configuration['mode'],
      'request' => $request_method,
      'responsetype' => $response_type,
      'encoding' => 'UTF-8',
    ];

    return $parameters;
  }

  /**
   * With each Server API request the following parameters must always be submitted.
   * @see 3.1.2 Standard parameter (Technical Reference for Client API)
   *
   * @param array $plugin_configuration
   * @param string $request_method
   * @return array
   */
  public function getServerApiStandardParameters(array $plugin_configuration, $request_method) {
    $parameters = [
      'mid' => $plugin_configuration['merchant_id'],
      'portalid' => $plugin_configuration['portal_id'],
      'key' => md5($plugin_configuration['key']),
      'api_version' => '3.10',
      'mode' => $plugin_configuration['mode'],
      'request' => $request_method,
      'encoding' => 'UTF-8',
    ];

    return $parameters;
  }

  /**
   * Calculates the hash value required in Client API requests.
   *
   * @param array $data
   * @param string $securitykey
   * @return string
   */
  public function generateHash(array $data, $securitykey) {
    // Sort by keys.
    ksort($data);

    // Hash code.
    $hashstr = '';
    foreach ($data as $key => $value) {
      $hashstr .= $data[$key];
    }
    $hashstr .= $securitykey;
    $hash = md5($hashstr);

    return $hash;
  }

  /**
   * {@inheritdoc}
   */
  public function processHttpPost(array $form_parameters, $client_api = TRUE) {
    $url = $client_api ? self::CLIENT_API_URL : self::SERVER_API_URL;

    try {
      $response = $this->http_client->post($url, [
        'form_params' => $form_parameters,
      ]);
    } catch (ClientException $e) {
      $response = $e->getResponse();
      $data = json_decode($response->getBody()->getContents());
      // TODO: handle multiple errors.
      throw new Exception($data->errors[0]->message, $e->getCode(), $e);
    } catch (ConnectException $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }

    if ($url == self::SERVER_API_URL) {
      $response_result = explode("\n", trim($response->getBody()->getContents()));
      $response_result =  $this->parseResponse($response_result);
    }
    else {
      $response_result = $response->getBody()->getContents();
    }

    return json_decode($response_result);
  }

  /**
   * @param array $responseRaw
   * @return array
   */
  protected function parseResponse(array $responseRaw = array()) {
    $result = [];

    if (count($responseRaw) == 0) {
      return $result;
    }

    foreach ($responseRaw as $key => $line) {
      $pos = strpos($line, "=");

      if ($pos === FALSE) {
        if (strlen($line) > 0) {
          $result[$key] = $line;
        }
        continue;
      }

      $lineArray = explode('=', $line);
      $resultKey = array_shift($lineArray);
      $result[$resultKey] = implode('=', $lineArray);
    }

    return json_encode($result);
  }
}
