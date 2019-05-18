<?php

namespace Drupal\Tests\commerce_avatax\Kernel;

use Drupal\commerce_avatax\Plugin\Commerce\TaxType\Avatax;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the API client factory.
 *
 * @group commerce_avatax
 */
class ClientFactoryTest extends KernelTestBase {

  /**
   * The tax type plugin.
   *
   * @var \Drupal\commerce_tax\Plugin\Commerce\TaxType\TaxTypeInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'commerce',
    'commerce_order',
    'commerce_price',
    'commerce_avatax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config = [
      'account_id' => 'DUMMY ACCOUNT',
      'license_key' => 'DUMMY KEY',
      'api_mode' => 'development',
    ];
  }

  /**
   * Test that the Guzzle client is properly configured.
   */
  public function testClientFactory() {
    $client_factory = $this->container->get('commerce_avatax.client_factory');

    $client = $client_factory->createInstance($this->config);

    $this->assertEquals('https://sandbox-rest.avatax.com/', $client->getConfig('base_uri'));
    $headers = $client->getConfig('headers');
    $this->assertEquals('Basic ' . base64_encode($this->config['account_id'] . ':' . $this->config['license_key']), $headers['Authorization']);
    $this->assertEquals('a0o33000003waOC', $headers['x-Avalara-UID']);
    $server_machine_name = gethostname();
    $this->assertEquals("Drupal Commerce; Version [8.x-1.x]; REST; V2; [$server_machine_name]", $headers['x-Avalara-Client']);

    $this->config['api_mode'] = 'production';
    $production_client = $client_factory->createInstance($this->config);
    $this->assertEquals('https://rest.avatax.com/', $production_client->getConfig('base_uri'));
  }

}
