<?php

namespace Drupal\real_estate_rets;

use Drupal\Core\Queue\RequeueException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use PHRETS\Configuration;
use PHRETS\Session;

/**
 * Fetches a data from RETS server.
 */
class RetsFetcher implements RetsFetcherInterface {
  use DependencySerializationTrait;
  /**
   * The rets settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $retsSettings;

  protected $connect;

  protected $rets;

  /**
   * Constructs a RetsFetcher.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRetsData(array $query) {
    $data = '';

    // Reconnect if isn't connected.
    if (!$this->connect) {
      $this->connectRetsServer($query);
    }

    try {

      // Fields, that will be fetched.
      // Input string can look like 'ListPrice:field_price,CityName:field_city'
      // so make it looks like 'ListPrice,CityName'.
      $select = preg_replace('/:\w+/', '', $query['select']);
      $select = preg_replace('/\r\n|\r|\n/', '', $select);
      $select = preg_replace('/\s+/', '', $select);

      // Add key field to be fetched.
      $key_field = trim(preg_replace('/:\w+/', '', $query['key_field']));

      $data = $this->rets->Search(
        $query['resource'],
        $query['class'],
        $query['query'],
        [
          'QueryType' => $query['dmql'],
      // Count and records.
          'Count' => 1,
          'Format' => $query['format'],
          'Limit' => $query['limit'],
      // Give system names.
          'StandardNames' => (int) $query['standardnames'],
          'Select' => $key_field . ',' . $select,
        ]
      );

      // var_dump($data);
    }
    catch (RequestException $exception) {
      watchdog_exception('real_estate_rets', $exception);
    }

    // Return mapping info too.
    $data_set = [
      'entity' => $query['entity'],
      'key_field' => $query['key_field'],
      'select' => $query['select'],
      'data' => $data,
    ];

    return $data_set;
  }

  /**
   * {@inheritdoc}
   */
  protected function connectRetsServer($query) {
    try {

      // Setup configuration. Used \PHRETS\Configuration.
      $config = new Configuration();
      $config->setLoginUrl($query['login_url']);
      $config->setUsername($query['username']);
      $config->setPassword($query['password']);

      $config->setRetsVersion($query['rets_version']);
      $config->setUserAgent($query['user_agent']);
      $config->setUserAgentPassword($query['user_agent_password']);
      $config->setHttpAuthenticationMethod($query['http_authentication']);
      $config->setOption('use_post_method', $query['use_post_method']);
      $config->setOption('disable_follow_location', $query['disable_follow_location']);

      // Get a session ready using the configuration. Used \PHRETS\Session.
      $this->rets = new Session($config);

      // Make the first request.
      $this->connect = $this->rets->Login();
    }
    catch (RequeueException $exception) {
      watchdog_exception('real_estate_rets', $exception);
    }
  }

}
