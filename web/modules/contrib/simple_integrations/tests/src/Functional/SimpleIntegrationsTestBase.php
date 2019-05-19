<?php

namespace Drupal\Tests\simple_integrations\Functional;

use Drupal\simple_integrations\ConnectionClient;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Base test for the Simple Integrations module.
 */
abstract class SimpleIntegrationsTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simple_integrations'];

  /**
   * An integration.
   *
   * @var \Drupal\simple_integrations\IntegrationInterface
   */
  public $integration;

  /**
   * A connection client.
   *
   * @var \Drupal\simple_integrations\ConnectionClient
   */
  public $connection;

  /**
   * Perform test setup tasks.
   *
   * Create an integration with the config provided.
   *
   * @param array $setup_config
   *   The config for the integration that needs to be created.
   */
  public function setUpConnection(array $setup_config) {
    $default_config = $this->getDefaultConfig();
    $integration_config = array_merge($default_config, $setup_config);

    // Create a new integration.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('integration');
    $integration = $entity_storage->create($integration_config);
    $integration->save();
    $this->integration = $integration;

    // Create a mock handler and queue two responses.
    $mock = new MockHandler([
      new Response(200),
      new Response(401),
    ]);

    // Create a connection client.
    $handler = HandlerStack::create($mock);
    $connection = new ConnectionClient(['handler' => $handler]);
    $connection->setIntegration($this->integration);
    $connection->configure();

    $this->connection = $connection;
  }

  /**
   * Get the default configuration for creating a test integration.
   *
   * @return array
   *   An array of config data.
   */
  protected function getDefaultConfig() {
    return [
      'id' => 'test_integration',
      'label' => 'Test integration',
      'active' => TRUE,
      'debug_mode' => FALSE,
      'external_end_point' => 'https://httpbin.org/get',
      'auth_type' => 'none',
      'auth_user' => '',
      'auth_key' => '',
    ];
  }

}
