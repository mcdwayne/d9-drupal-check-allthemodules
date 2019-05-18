<?php

namespace Drupal\dashboard_connector;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * The dashboard connector implementation.
 */
class DashboardConnector implements DashboardConnectorInterface {

  /**
   * The HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The dashboard connector config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * DashboardConnector constructor.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory) {
    $this->client = $client;
    $this->config = $config_factory->get('dashboard_connector.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function sendSnapshot(array $snapshot) {
    $uri = rtrim($this->config->get('base_uri'), '/') . '/snapshots';
    $this->client->request('POST', $uri, [
      'json' => $snapshot,
      'auth' => [$this->config->get('username'), $this->config->get('password')],
    ]);
  }

}
