<?php

namespace Drupal\smartwaiver\Service;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Drupal\smartwaiver\ClientInterface;
use Drupal\key\KeyRepositoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class Client implements ClientInterface {

  const BASE_URI = 'https://api.smartwaiver.com';

  const API_VERSION = 'v4';

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $http;

  /**
   * A config factory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The immutable smartwaiver config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * An array of options to send with the http request.
   *
   * @var \Symfony\Component\HttpFoundation\ParameterBag
   */
  protected $options;

  /**
   * Creates a new instance of a Smartwaiver client.
   */
  public function __construct(GuzzleClient $http_client, ConfigFactory $config_factory, KeyRepositoryInterface $key_repository) {
    $this->http = $http_client;
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('smartwaiver.config');
    $this->keyRepository = $key_repository;
    $this->options = new ParameterBag();
  }

  /**
   * {@inheritdoc}
   */
  public function waiver($waiver_id) {
    $response = $this->get('waivers' . '/' . $waiver_id);
    return !empty($response['waiver']) ? (object) $response['waiver'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function waivers($options = []) {
    return $this->get('waivers');
  }

  /**
   * {@inheritdoc}
   */
  public function templates() {
    return $this->get('templates');
  }

  /**
   * @param string $name
   *
   * @return string
   */
  protected function apiKey($name = 'api_key') {
    if ($key_id = $this->config->get($name)) {
      $key = $this->keyRepository->getKey($key_id);
      $value = trim($key->getKeyValue());
      return $value;
    }
  }

  /**
   * @param $path
   *
   * @return string
   */
  protected function url($path) {
    return join('/', [$this->baseUri(), self::API_VERSION, $path]);
  }

  /**
   * @return string
   */
  private function baseUri() {
    return self::BASE_URI;
  }

  /**
   * @param $path
   * @param array $options
   *
   * @return array|mixed
   */
  private function get($path, $options = []) {
    return $this->request('get', $path, $options);
  }

  /**
   * @param $method
   * @param $path
   * @param $options
   *
   * @return array|mixed
   */
  private function request($method, $path, $options) {
    $this->options->replace($options);
    $this->options->set('headers', [
      'sw-api-key' => $this->apiKey('api_key')
    ]);
    $request = $this->http->request($method, $this->url($path), $this->options->all());
    if (!empty($request->getBody())) {
      return Json::decode($request->getBody()->getContents());
    }
    return [];
  }

}
