<?php

/**
 * @file
 */

namespace Drupal\youtrack\API;

use Drupal\Core\Config\ConfigFactoryInterface;
use YouTrack\Connection;

class ConnectionManager {

  /**
   * YouTrack API connection handler.
   *
   * @var \YouTrack\Connection
   */
  protected $connection;

  /**
   * The youtrack.settings config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a ConnectionManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('youtrack.settings');
  }

  /**
   * Connect to YouTrack REST API.
   *
   * @param $url
   * @param $login
   * @param $password
   *
   * @return \YouTrack\Connection
   */
  public function connect($url, $login, $password) {
    return new Connection($url, $login, $password);
  }

  /**
   * Get YouTrack connection object to query YouTrack API.
   *
   * @return \YouTrack\Connection
   */
  public function getConnection() {
    if (isset($this->connection)) {
      return $this->connection;
    }

    $this->connection = $this->connect(
      $this->config->get('youtrack_url'),
      $this->config->get('youtrack_login'),
      $this->config->get('youtrack_password')
    );

    return $this->connection;
  }

}