<?php

namespace Drupal\dropshark\Request;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Class Request.
 */
class Request implements RequestInterface {

  /**
   * The URL to the DropShark backend.
   *
   * @var string
   */
  protected $host;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The site token.
   *
   * @var string
   */
  protected $token;

  /**
   * DropSharkRequest constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration options.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   HTTP client.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ClientInterface $httpClient, StateInterface $state) {
    $config = $configFactory->get('dropshark.settings');
    $this->host = $config->get('host');
    $this->httpClient = $httpClient;
    $this->token = $state->get('dropshark.site_token');
  }

  /**
   * {@inheritdoc}
   */
  public function checkToken() {
    $result = new \stdClass();

    $options = $this->requestOptions();

    try {
      $response = $this->httpClient->request('get', $this->host . '/sites/token', $options);
      $result->code = $response->getStatusCode();
      $result->data = \GuzzleHttp\json_decode($response->getBody());
    }
    catch (ClientException $e) {
      if ($e->hasResponse()) {
        $result->code = $e->getResponse()->getStatusCode();
        $result->data = \GuzzleHttp\json_decode($e->getResponse()->getBody());
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken($email, $password, $siteId) {
    $params = ['user' => $email, 'password' => $password, 'site_id' => $siteId];
    $options = $this->requestOptions($params);

    $result = new \stdClass();
    try {
      $response = $this->httpClient->request('post', $this->host . '/sites/token', $options);
      $result->code = $response->getStatusCode();
      $result->data = \GuzzleHttp\json_decode($response->getBody());
    }
    catch (ClientException $e) {
      if ($e->hasResponse()) {
        $result->code = $e->getResponse()->getStatusCode();
        $result->data = \GuzzleHttp\json_decode($e->getResponse()->getBody());
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function postData(array $data) {
    $options = $this->requestOptions($data);
    $result = new \stdClass();

    try {
      $response = $this->httpClient->request('post', $this->host . '/data', $options);
      $result->code = $response->getStatusCode();
      $result->data = \GuzzleHttp\json_decode($response->getBody());
    }
    catch (ClientException $e) {
      if ($e->hasResponse()) {
        $result->code = $e->getResponse()->getStatusCode();
        $result->data = \GuzzleHttp\json_decode($e->getResponse()->getBody());
      }
    }

    return $result;
  }

  /**
   * Prepares an array of options for Guzzle requests.
   *
   * @param array $params
   *   Request parameters.
   *
   * @return array
   *   Options to use for a Guzzle request.
   */
  protected function requestOptions(array $params = []) {
    $options = [];

    if (!empty($params)) {
      $options['form_params'] = $params;
    }

    if ($this->token) {
      $options['headers']['Authorization'] = $this->token;
    }

    return $options;
  }

}
