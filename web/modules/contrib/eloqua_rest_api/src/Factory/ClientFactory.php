<?php

/**
 * @file
 * Contains \Drupal\eloqua_rest_api\Factory\ClientFactory.
 */

namespace Drupal\eloqua_rest_api\Factory;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Eloqua\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ClientFactory implements ContainerInjectionInterface  {

  /**
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, ModuleHandlerInterface $moduleHandler) {
    $this->configFactory = $configFactory;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns an authenticated instance of the Elomentary REST API client.
   *
   * @param string $version
   *   (Optional) The desired API version (1.0, 2.0, etc.) of the returned
   *   client. Defaults to 2.0.
   *
   * @return Client|NULL
   *   If the module is successfully configured, a fully configured Elomentary
   *   client will be returned. Otherwise, NULL will be returned.
   */
  public function get($version = '2.0') {
    // Base configurations for instantiating an Elomentary client.
    $config = $this->configFactory->get('eloqua_rest_api.settings');
    $site = $config->get('eloqua_rest_api_site_name');
    $login = $config->get('eloqua_rest_api_login_name');
    $password = $config->get('eloqua_rest_api_login_password');
    $base_url = $config->get('eloqua_rest_api_base_url');
    $api_timeout = $config->get('eloqua_rest_api_timeout');

    // Ensure the $base_url is either a valid base URL string, or NULL.
    $base_url = $base_url === '' ? NULL : $base_url;

    // Add a default API timeout for backwards compatibility.
    $api_timeout = $api_timeout ?: 10;

    if ($site && $login && $password) {
      // Instantiate the Elomentary client.
      $client = new Client();
      $client->setOption('version',  $version);
      $client->setOption('timeout', $api_timeout);
      $client->authenticate($site, $login, $password, $base_url);

      // Allow other modules to alter the client before it is used / returned.
      $this->moduleHandler->alter('eloqua_rest_api_client', $client);

      return $client;
    }
    else {
      return NULL;
    }
  }

}
