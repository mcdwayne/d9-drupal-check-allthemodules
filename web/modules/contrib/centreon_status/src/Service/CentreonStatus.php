<?php

namespace Drupal\centreon_status\Service;

use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactory;

/**
 * Get json result.
 */
class CentreonStatus {

  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * CentreonStatus constructor.
   */
  public function __construct(ConfigFactory $configFactory) {
    $this->configFactory = $configFactory->get('centreon_status.settings');

  }

  /**
   * Get token from Centreon API.
   *
   * @return string
   *   $token->{'authToken'};
   */
  public function getToken() {
    $client = \Drupal::httpClient();
    $response = $client->request('POST', $this->configFactory->get('url_ws') . "/api/index.php?action=authenticate", [
      'form_params' => [
        'username' => $this->configFactory->get('username'),
        'password' => $this->configFactory->get('password'),
      ],

    ]);
    try {
      // Expected result.
      $token = json_decode($response->getBody());
      return $token->{'authToken'};
    }
    catch (RequestException $e) {
      watchdog_exception('centreon_status', $e->getMessage());
    }
  }

  /**
   * Get Realtime status from json.
   *
   * @return array
   *   renderable array.
   */
  public function getRealtime($action) {
    $client = \Drupal::httpClient();
    $url = rtrim($this->configFactory->get('url_ws'), '/');
    if ($action == "hosts") {
      $response = $client->request('GET', $url . '/api/index.php?object=centreon_realtime_hosts&action=list', [
        'headers' => [
          'centreon-auth-token' => $this->getToken(),
        ],
      ]);
    }
    elseif ($action == 'services') {
      $response = $client->request('GET', $url . '/api/index.php?object=centreon_realtime_services&action=list&limit=1000', [
        'headers' => [
          'centreon-auth-token' => $this->getToken(),
        ],
      ]);
    };
    try {
      return json_decode($response->getBody());
    }
    catch (RequestException $e) {
      watchdog_exception('centreon_status', $e->getMessage());
    }
  }

}
