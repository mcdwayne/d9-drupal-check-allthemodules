<?php

/**
 * @file
 * Contains \Drupal\Tests\eloqua_rest_api\Unit\Factory\ClientFactory.
 */

namespace Drupal\Tests\eloqua_rest_api\Unit\Factory;

use Drupal\eloqua_rest_api\Factory\ClientFactory;
use Drupal\Tests\eloqua_rest_api\Unit\EloquaConfigBase;

/**
 * Tests instantiation of Elomentary REST API clients.
 *
 * @group eloqua
 */
class ClientFactoryTest extends EloquaConfigBase {

  /**
   * Conditionally load in the mock client (required because qa.drupal.org does
   * not support composer required libraries).
   */
  protected function setUp() {
    parent::setUp();

    if (!class_exists('\Eloqua\Client')) {
      require_once(__DIR__ . '/../Mock/Client.php');
    }
  }

  /**
   * Ensure the client getter behaves as expected when no Eloqua credentials
   * have yet been configured.
   *
   * @test
   */
  public function shouldReturnNullWithNoCredentials() {
    $moduleHandlerMock = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockConfigWithCredentials();

    // Ensure a configuration object is pulled for this module's settings.
    $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

    // Given no credentials have been configured, ensure no client is returned.
    $factory = new ClientFactory($configFactoryObserver, $moduleHandlerMock);
    $this->assertNull($factory->get());
  }

  /**
   * Ensure the client is instantiated and returned properly when credentials
   * are configured.
   *
   * @test
   */
  function shouldInstantiateClientDefaults() {
    // Ensure a module alter is called on the instantiated client.
    $moduleHandlerObserver = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $moduleHandlerObserver->expects($this->once())
      ->method('alter')
      ->with($this->equalTo('eloqua_rest_api_client'), $this->isInstanceOf('\Eloqua\Client'));

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockConfigWithCredentials([
      'eloqua_rest_api_site_name' => 'SiteName',
      'eloqua_rest_api_login_name' => 'Login.Name',
      'eloqua_rest_api_login_password' => 'batteryhorsestaple',
      'eloqua_rest_api_base_url' => NULL,
      'eloqua_rest_api_timeout' => 10,
    ]);

    // Ensure a configuration object is pulled for this module's settings.
    $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

    // Given credentials have been configured, ensure a client is returned.
    $factory = new ClientFactory($configFactoryObserver, $moduleHandlerObserver);
    $client = $factory->get();
    $this->assertInstanceOf('\Eloqua\Client', $client);

    // While we're at it, ensure the correct default API version was used and
    // the expected default base URL was used.
    $this->assertEquals($client->getOption('version'), '2.0');
    $this->assertEquals($client->getOption('timeout'), 10);
    $this->assertEquals($client->getOption('base_url'), 'https://secure.p01.eloqua.com/API/REST');
  }

  /**
   * Ensure the specified API version is set on the client.
   *
   * @test
   */
  function shouldSetClientOptionApiVersion() {
    $expectedVersion = '1.0';
    $moduleHandlerObserver = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockConfigWithCredentials([
      'eloqua_rest_api_site_name' => 'SiteName',
      'eloqua_rest_api_login_name' => 'Login.Name',
      'eloqua_rest_api_login_password' => 'batteryhorsestaple',
      'eloqua_rest_api_base_url' => NULL,
      'eloqua_rest_api_timeout' => 10,
    ]);

    // Ensure a configuration object is pulled for this module's settings.
    $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

    // Return a client factory with a specified version.
    $factory = new ClientFactory($configFactoryObserver, $moduleHandlerObserver);
    $client = $factory->get($expectedVersion);
    $this->assertEquals($client->getOption('version'), $expectedVersion);
  }

  /**
   * Ensure the specified API timeout is set on the client.
   *
   * @test
   */
  function shouldSetClientOptionTimeout() {
    $expectedTimeout = '20';
    $moduleHandlerObserver = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockConfigWithCredentials([
      'eloqua_rest_api_site_name' => 'SiteName',
      'eloqua_rest_api_login_name' => 'Login.Name',
      'eloqua_rest_api_login_password' => 'batteryhorsestaple',
      'eloqua_rest_api_base_url' => NULL,
      'eloqua_rest_api_timeout' => $expectedTimeout,
    ]);

    // Ensure a configuration object is pulled for this module's settings.
    $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

    // Return a client factory with a specified version.
    $factory = new ClientFactory($configFactoryObserver, $moduleHandlerObserver);
    $client = $factory->get();
    $this->assertEquals($client->getOption('timeout'), $expectedTimeout);
  }

  /**
   * Ensure the specified base URL is set on the client.
   *
   * @test
   */
  function shouldSetClientOptionBaseURL() {
    $moduleHandlerObserver = $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface');
    $expectedConfigs = [
      'eloqua_rest_api_site_name' => 'SiteName',
      'eloqua_rest_api_login_name' => 'Login.Name',
      'eloqua_rest_api_login_password' => 'batteryhorsestaple',
      'eloqua_rest_api_base_url' => 'https://secure.p02.eloqua.com/API/REST',
      'eloqua_rest_api_timeout' => 10,
    ];

    // Ensure configuration is pulled for each credential.
    $configObserver = $this->getMockConfigWithCredentials($expectedConfigs);

    // Ensure a configuration object is pulled for this module's settings.
    $configFactoryObserver = $this->getConfigFactoryReturning($configObserver);

    // Return a client factory with a specified version.
    $factory = new ClientFactory($configFactoryObserver, $moduleHandlerObserver);
    $client = $factory->get();
    $this->assertEquals($client->getOption('base_url'), $expectedConfigs['eloqua_rest_api_base_url']);
  }

}
