<?php

namespace Drupal\druminate\Luminate;

use GuzzleHttp\Client;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;

/**
 * Class DruminateApi.
 *
 * @package Drupal\druminate\Luminate
 */
class DruminateApi {

  /**
   * The Http Client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The ConvioConnect Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configSettings;

  /**
   * DruminateApi constructor.
   *
   * @param \GuzzleHttp\Client $client
   *   The Http Client.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory.
   */
  public function __construct(Client $client, ConfigFactory $config) {
    $this->client = $client;
    $this->configSettings = $config->getEditable('druminate.settings');
  }

  /**
   * Helper function used to get Convio Api credentials.
   *
   * @return array|bool
   *   Array of Api credentials.
   */
  public function getSettings() {
    if ($this->configSettings->get('api_key')) {

      return [
        'secure_url' => $this->configSettings->get('secure_url'),
        'non_secure_url' => $this->configSettings->get('non_secure_url'),
        'settings' => [
          'api_key' => $this->configSettings->get('api_key'),
          'v' => '1.0',
          'suppress_response_codes' => 'true',
          'sign_redirects' => 'true',
          'response_format' => 'json',
        ],
      ];
    }
    else {
      // TODO: Attach a link to the message.
      drupal_set_message($this->t('Please check the Druminate settings.'), 'warning');
      return FALSE;
    }
  }

  /**
   * Helper function used to generate authentication tokens.
   *
   * @return array|bool
   *   Authentication Token and the Session ID.
   */
  public function getAuth() {
    if ($this->getSettings() &&
      $this->configSettings->get('login_name') &&
      $this->configSettings->get('login_password')) {

      $base_settings = $this->getSettings();

      $params = [
        'method' => 'getLoginUrl',
        'login_name' => $this->configSettings->get('login_name'),
        'login_password' => $this->configSettings->get('login_password'),
      ];

      $options = [
        'query' => array_merge($base_settings['settings'], $params),
      ];

      if (!empty($base_settings['secure_url'])) {
        $base_url = $base_settings['secure_url'];
      }
      elseif (!empty($base_settings['non_secure_url'])) {
        $base_url = $base_settings['non_secure_url'];
      }
      else {
        return FALSE;
      }

      $url = Url::fromUri($base_url . '/CRConsAPI', $options);

      $request = $this->client->get($url->toString());
      $response = json_decode((string) $request->getBody());

      if ($response->getLoginUrlResponse) {
        if ($response->getLoginUrlResponse->url && $response->getLoginUrlResponse->token) {
          $url = explode(';', $response->getLoginUrlResponse->url);
          if (isset($url[1]) && !empty($url[1])) {
            return [
              'session_id' => $url[1],
              'token' => $response->getLoginUrlResponse->token,
            ];
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Helper function used to perform Druminate "get" requests.
   *
   * @param string $servlet
   *   The client or server side servlet.
   * @param string $method
   *   The api method to be called.
   * @param array $params
   *   Extra params that should be passed to the api.
   * @param string $request_type
   *   Determine whether a get or post request should be made.
   * @param bool $auth_required
   *   Determines whether or not an auth token should be added to request.
   * @param bool $custom_url
   *   Consume Api data from a custom url.
   *
   * @return bool|mixed
   *   Api call result. Will return false if the call fails.
   */
  public function request($servlet, $method, array $params, $request_type = 'get', $auth_required = FALSE, $custom_url = FALSE) {
    if ($base_settings = $this->getSettings()) {

      if (!empty($base_settings['secure_url'])) {
        $base_url = $base_settings['secure_url'];
      }
      elseif (!empty($base_settings['non_secure_url'])) {
        $base_url = $base_settings['non_secure_url'];
      }
      else {
        return FALSE;
      }

      $query = array_merge(['method' => $method], $base_settings['settings'], $params);

      $options = [
        'query' => $query,
      ];

      if ($auth_required) {
        $auth = $this->getAuth();
        if (!empty($auth)) {
          $options['query']['auth'] = $auth['token'];
          $url = Url::fromUri($base_url . '/' . $servlet . ';' . $auth['session_id'], $options);
        }
      }
      else {
        $url = Url::fromUri($base_url . '/' . $servlet, $options);
      }

      if ($request_type == 'get') {
        $request = $this->client->get($url->toString());
      }
      else {
        $request = $this->client->post($url->toString());
      }

      $response = $request->getBody()->__toString();
      // Remove any leading or trailing invalid characters.
      $response = preg_replace('@^[^{\[]+@', '', $response);
      $response = preg_replace('@[^}\]]+$@', '', $response);

      $converted = json_decode($response);

      return $converted;
    }
    else {
      return FALSE;
    }
  }

}
