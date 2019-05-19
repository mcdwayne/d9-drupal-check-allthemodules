<?php

/**
 * @file
 * Allows the site to send and receive user contacts to and from Text Marketer.
 */

namespace Drupal\textmarketer_contacts;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\Client as Request;

/**
 * Class ClientFactory.
 */
class ClientFactory {

  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactory $config_factory) {

    $this->configFactory = $config_factory;
  }

  /**
   * Return a configured Client object.
   */
  public function request() {

    $config = $this->configFactory->get('textmarketer_contacts.settings');
    $username = $config->get('username');
    $password = $config->get('password');
    $api_url  = $config->get('api_url');
    $base_url = "https://{$username}:{$password}@{$api_url}";
    $config   = ['base_uri' => $base_url];
    $client   = new Request($config);

    return $client;
  }

}
