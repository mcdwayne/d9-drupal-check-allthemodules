<?php

namespace Drupal\zuora\Rest;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\zuora\Exception\ZuoraException;
use Drupal\zuora\ZuoraClientBase;

class Client extends ZuoraClientBase {

  /**
   *
   */
  const ZUORA_PRODUCTION = 'https://rest.zuora.com';

  /**
   *
   */
  const ZUORA_SANDBOX = 'https://rest.apisandbox.zuora.com';

  /**
   *
   */
  const ZUORA_API_VERSION = 'v1';

  protected $httpClient;

  public function __construct(ConfigFactoryInterface $config_factory, ClientFactory $client_factory) {
    parent::__construct($config_factory);

    $base_uri = ($this->isSandboxed()) ? self::ZUORA_SANDBOX : self::ZUORA_PRODUCTION;
    $this->httpClient = $client_factory->fromOptions([
      'base_uri' => $base_uri,
    ]);
  }


  /**
   * Perform GET request.
   *
   * @param $uri
   * @param null $zuora_version
   *   The version of api to send in header.
   *
   * @return array|mixed
   * @internal param array|NULL $body
   *
   */
  public function get($uri, $zuora_version = NULL) {
    return $this->httpRequest('GET', $uri, NULL, $zuora_version);
  }

  /**
   * Perform POST request.
   *
   * @param $uri
   * @param array $body
   *
   * @param NULL $zuora_version
   *   The version of api to send in header.
   *
   * @return array|mixed
   */
  public function post($uri, array $body, $zuora_version = NULL) {
    return $this->httpRequest('POST', $uri, $body, $zuora_version);
  }

  /**
   * Perform PUT request.
   *
   * @param $uri
   * @param array $body
   *
   * @param NULL $zuora_version
   *   The version of api to send in header.
   *
   * @return array|mixed
   */
  public function put($uri, array $body, $zuora_version = NULL) {
    return $this->httpRequest('PUT', $uri, $body, $zuora_version);
  }

  /**
   * Builds a drupal_http_request call.
   *
   * @param $type
   * @param $uri
   * @param array|NULL $body
   *
   * @param NULL $zuora_version
   *   The version of api to send in header.
   *
   * @return array|mixed
   * @throws \Drupal\zuora\Exception\ZuoraException
   */
  public function httpRequest($type, $uri, array $body = NULL, $zuora_version = null) {
    $response = $this->httpClient->request($type, '/' . self::ZUORA_API_VERSION . $uri, [
      'json' => $body,
      'headers' => $this->httpHeaders($zuora_version),
    ]);
    $data = json_decode($response->getBody()->getContents(), TRUE);

    if ($response->getStatusCode() == 200) {
      return $data;
    }
    else {
      throw new ZuoraException("Zuora API error: {$data['error']}");
    }

  }

  /**
   * Returns HTTP request headers.
   *
   * @param NULL $zuora_version
   *   The version of api to send in header.
   *
   * @return array
   */
  protected function httpHeaders($zuora_version = NULL) {
    $headers = [
      'Accept' => 'application/json',
      'Content-Type' => 'application/json',
      'apiAccessKeyId' => $this->zuoraConfig->get('access_key_id'),
      'apiSecretAccessKey' => $this->zuoraConfig->get('access_secret_key'),
    ];
    if($zuora_version != NULL){
      $headers['zuora-version'] = $zuora_version;
    }
    $headers = $headers;
    return $headers;
  }

}
