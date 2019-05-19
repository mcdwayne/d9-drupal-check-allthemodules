<?php

namespace Drupal\webfactory_slave\Services;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\webfactory_slave\MasterClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;

/**
 * Webfactory Master REST Client.
 *
 * @package Drupal\webfactory_slave\Services
 */
class MasterRestClient implements MasterClientInterface {

  /**
   * Webfactory slave settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * Drupal user auth cookie.
   *
   * @var \GuzzleHttp\Cookie\CookieJar
   */
  private $cookie;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->config = \Drupal::config('webfactory_slave.settings');
    $this->createSession();
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelsData($satellite_id) {
    $data = [];

    if (!isset($this->httpClient)) {
      return $data;
    }

    $token = $this->httpClient->get('/rest/session/token', [
      'cookies' => $this->cookie,
    ])->getBody(TRUE);

    $token = $token->__toString();

    $url = '/webfactory_master/channels/' . $satellite_id;
    $response = $this->httpClient->get($url, [
      'cookies' => $this->cookie,
      'headers' => [
        'Accept' => 'application/json',
        'Content-type' => 'application/hal+json',
        'X-CSRF-Token' => $token,
      ],
      'query' => [
        '_format' => 'hal_json',
      ],
    ]);
    $data = json_decode($response->getBody());

    if ($data == NULL) {
      $data = [];
    }
    else {
      $data = (array) $data;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesData($channel_id, $uuid = NULL, $limit = NULL, $offset = NULL) {
    $data = [];

    if (!isset($this->httpClient)) {
      return $data;
    }

    $url = '/webfactory_master/channel/' . $channel_id;

    if ($uuid !== NULL) {
      $url .= '/' . $uuid;
    }

    try {
      $response = $this->httpClient->get($url, [
        'cookies' => $this->cookie,
        'headers' => [
          'Accept' => 'application/hal+json',
          'Content-type' => 'application/hal+json',
        ],
        'query' => [
          '_format' => 'hal_json',
          'limit' => $limit,
          'offset' => $offset,
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
    }
    catch (RequestException $e) {

    }

    return $data;
  }

  /**
   * Initialize a session with the master.
   */
  protected function createSession() {
    $master_ip = $this->getConfig('master_ip');
    $auth = $this->getConfig('authentificate');

    if (!empty($master_ip) && !empty($auth)) {

      $this->httpClient = new Client([
        'base_uri' => 'http://' . $master_ip,
        'cookies' => TRUE,
        'allow_redirects' => TRUE,
      ]);

      // With self sign certificate only.
      /* $this->httpClient->setDefaultOption('verify', FALSE); */

      $this->auth($auth['username'], $auth['password']);
    }
    else {
      $config_url = Url::fromRoute('webfactory_slave.settings');

      drupal_set_message(t('No master was configured ! Got to !link to configure it', [
        '!link' => Link::fromTextAndUrl($config_url->toString(), $config_url)->toString(),
      ]), 'error');
    }
  }

  /**
   * Manage authentification to the master.
   *
   * @param string $name
   *   Drupal user login.
   * @param string $password
   *   Drupal user password.
   */
  protected function auth($name, $password) {
    $this->cookie = new CookieJar();
    $this->httpClient->post('/user/login', [
      "form_params" => [
        "name" => $name,
        "pass" => $password,
        'form_id' => 'user_login_form',
      ],
      'cookies' => $this->cookie,
    ]);
  }

  /**
   * Helper to ease config reading.
   *
   * @param string $key
   *   Config entry key.
   *
   * @return mixed
   *   Value of entry key.
   */
  protected function getConfig($key) {
    return $this->config->get($key);
  }

}
