<?php

namespace Drupal\bitlink;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Exception\RequestException;

class BitlinkAPIService {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a BitlinkAPIService object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Returns Bitlink API configuration.
   *
   * @return array $config
   *   Bitlink API configuration.
   */
  public function getConfig() {
    $bitlink_config = $this->configFactory->get('bitlink.settings');
    $config_key_id = $bitlink_config->get('bitlink_settings_file_key');
    $config_json = \Drupal::service('key.repository')->getKey($config_key_id)->getKeyValue();

    $config = \GuzzleHttp\json_decode($config_json);

    return (array) $config;
  }

  /**
   * Method to get Bitly Group Information.
   *
   * @param array $data
   *   Optional data.
   *
   * @return array $response_data
   *   Shortened URL response returned from Bitlink.
   */
  public function getGroupsInfo($data = []) {
    $bitlink_config = $this->getConfig();

    if (!empty($data) && isset($data['api_base_url'])) {
      $ws_base_url = $data['api_base_url'];
    }
    else {
      $ws_base_url = $bitlink_config->get('api_base_url');
    }
    $ws_url = $ws_base_url . '/v4/groups';

    try {
      $client = \Drupal::httpClient();

      $oauth_details = $this->authenticate($data);

      $auth_token = 'Bearer ' . $oauth_details['access_token'];

      $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => $auth_token,
      ];

      $response = $client->get($ws_url, [
        'headers' => $headers,
      ]);

      $response_data_json = $response->getBody()->getContents();
      $response_data = (array) \GuzzleHttp\json_decode($response_data_json);

      if ($response->getStatusCode() == StatusCodes::HTTP_OK && !empty($response_data)) {
        return $response_data;
      }
    }
    catch (ClientException $e) {
      \Drupal::logger('bitlink')->error($e);
      $response_data = [
        'status' => 'failure',
        'data' => [
          'code' => $e->getCode(),
          'message' => $e->getMessage(),
        ],
      ];
      return $response_data;
    }
    catch (RequestException $e) {
      \Drupal::logger('bitlink')->error($e);
      $response_data = [
        'status' => 'failure',
        'data' => [
          'code' => $e->getCode(),
          'message' => $e->getMessage(),
        ],
      ];
      return $response_data;
    }
  }

  /**
   * Method to shorten the long URL.
   *
   * @param string $long_url
   *   Long URL to be shortened.
   * @param array $data
   *   Optional data.
   *
   * @return array $response_data
   *   Shortened URL response returned from Bitlink.
   */
  public function shorten($long_url, $data = []) {
    $bitlink_config = $this->getConfig();
    $group_guid = $bitlink_config->get('group_guid');

    if (!empty($group_guid)) {
      $ws_base_url = $bitlink_config->get('api_base_url');
      $ws_url = $ws_base_url . '/v4/shorten';

      try {
        $client = \Drupal::httpClient();

        $oauth_details = $this->authenticate($data);

        $auth_token = 'Bearer ' . $oauth_details['access_token'];

        $headers = [
          'Content-Type' => 'application/json',
          'Authorization' => $auth_token,
        ];

        $data = [
          'long_url' => $long_url,
          'group_guid' => $group_guid,
        ];

        $response = $client->post($ws_url, [
          'headers' => $headers,
          'body' => \GuzzleHttp\json_encode($data),
        ]);

        $response_data_json = $response->getBody()->getContents();
        $response_data = (array) \GuzzleHttp\json_decode($response_data_json);

        if ($response->getStatusCode() == StatusCodes::HTTP_OK && !empty($response_data)) {
          $response_data_final = [
            'status' => 'success',
            'data' => $response_data
          ];
          return $response_data_final;
        }
      }
      catch (ClientException $e) {
        \Drupal::logger('bitlink')->error($e);
        $response_data = [
          'status' => 'failure',
          'data' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
          ],
        ];
        return $response_data;
      }
      catch (RequestException $e) {
        \Drupal::logger('bitlink')->error($e);
        $response_data = [
          'status' => 'failure',
          'data' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
          ],
        ];
        return $response_data;
      }
    }
  }

  /**
   * Method to expand the Bitly short URL.
   *
   * @param string $short_url
   *   Bitly Short URL Id.
   * @param array $data
   *   Optional data.
   *
   * @return array $response_data
   *   Expanded URL response returned from Bitlink.
   */
  public function expand($short_url = '', $data = []) {
    $bitlink_config = $this->getConfig();
    $group_guid = $bitlink_config->get('group_guid');

    if (!empty($group_guid)) {
      $ws_base_url = $bitlink_config->get('api_base_url');
      $ws_url = $ws_base_url . '/v4/expand';

      try {
        $client = \Drupal::httpClient();

        $oauth_details = $this->authenticate($data);

        $auth_token = 'Bearer ' . $oauth_details['access_token'];

        $headers = [
          'Content-Type' => 'application/json',
          'Authorization' => $auth_token,
        ];

        $data = [
          'bitlink_id' => $short_url,
        ];

        $response = $client->post($ws_url, [
          'headers' => $headers,
          'body' => \GuzzleHttp\json_encode($data),
        ]);

        $response_data_json = $response->getBody()->getContents();
        $response_data = (array) \GuzzleHttp\json_decode($response_data_json);

        if ($response->getStatusCode() == StatusCodes::HTTP_OK && !empty($response_data)) {
          $response_data_final = [
            'status' => 'success',
            'data' => $response_data
          ];
          return $response_data_final;
        }
      }
      catch (ClientException $e) {
        \Drupal::logger('bitlink')->error($e);
        $response_data = [
          'status' => 'failure',
          'data' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
          ],
        ];
        return $response_data;
      }
      catch (RequestException $e) {
        \Drupal::logger('bitlink')->error($e);
        $response_data = [
          'status' => 'failure',
          'data' => [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
          ],
        ];
        return $response_data;
      }
    }
  }

  /**
   * Method to authenticate the site with Bitly API.
   *
   * @param array $data
   *   Data required for Bitly Authentication.
   *
   * @return array $oauth_data
   *   OAuth data containing access token.
   */
  public function authenticate($data = []) {
    $config = $this->getConfig();

    if (isset($data['api_base_url'])) {
      $ws_base_url = $data['api_base_url'];
    }
    else {
      $ws_base_url = $config->get('api_base_url');
    }

    $ws_url = $ws_base_url . '/oauth/access_token';

    if (empty($ws_base_url)) {
      $message = t('Endpoint URL is not configured.');
      \Drupal::logger('bitlink')->error($message);
      return '';
    }

    if (isset($_SESSION['oauth_details'])) {
      return (array) $_SESSION['oauth_details'];
    }

    if (isset($data['oauth_clientid'])) {
      $client_id = $data['oauth_clientid'];
    }
    else {
      $client_id = $config->get('oauth_clientid');
    }

    if (isset($data['oauth_clientsecret'])) {
      $client_secret = $data['oauth_clientsecret'];
    }
    else {
      $client_secret = $config->get('oauth_clientsecret');
    }

    if (isset($data['username'])) {
      $username = $data['username'];
    }
    else {
      $username = $config->get('username');
    }

    if (isset($data['password'])) {
      $password = $data['password'];
    }
    else {
      $password = $config->get('password');
    }

    $auth_token = base64_encode($client_id . ':' . $client_secret);

    $headers = [
      'content-type' => 'application/x-www-form-urlencoded',
      'Authorization' => "Basic $auth_token",
    ];

    $data = http_build_query([
      'grant_type' => 'password',
      'username' => $username,
      'password' => $password,
    ]);

    try {
      $client = \Drupal::httpClient();

      $response = $client->post($ws_url, ['headers' => $headers, 'body' => $data]);

      $response_data_json = $response->getBody()->getContents();
      $oauth_data = (array) \GuzzleHttp\json_decode($response_data_json);

      if ($response->getStatusCode() == \Drupal\bitlink\StatusCodes::HTTP_OK && !empty($oauth_data)) {
        if (!empty($oauth_data)) {
          $_SESSION['oauth_details'] = $oauth_data;

          $message = t('OAuth authenticated successfully.');
          \Drupal::logger('bitlink')->notice($message);
        }
        else {
          $message = t('There was an issue while establishing OAuth connection. Please check OAuth related configuration.');
          \Drupal::logger('bitlink')->error($message);
        }

        return (array) $oauth_data;
      }
    }
    catch (ClientException $e) {
      if ($e->getCode() == StatusCodes::HTTP_NOT_FOUND) {
        $message = t('Could not establish OAuth connection. Please check OAuth related configuration.');
        \Drupal::logger('bitlink')->error($message);
      }
    }
  }

}
