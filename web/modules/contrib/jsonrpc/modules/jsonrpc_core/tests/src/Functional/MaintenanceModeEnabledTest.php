<?php

namespace Drupal\Tests\jsonrpc_core\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\jsonrpc\Functional\JsonRpcTestBase;

/**
 * Test turning the maintenance mode on or off using JSON RPC.
 *
 * @group jsonrpc
 */
class MaintenanceModeEnabledTest extends JsonRpcTestBase {

  protected static $modules = [
    'jsonrpc',
    'jsonrpc_core',
    'basic_auth',
    'serialization',
  ];

  /**
   * Tests enabling the maintenance mode.
   */
  public function testEnablingMaintenanceMode() {

    $enabled_request = [
      'jsonrpc' => '2.0',
      'method' => 'maintenance_mode.isEnabled',
      'params' => [
        'enabled' => TRUE,
      ],
      'id' => 'maintenance_mode_enabled',
    ];

    // Assert that anonymous users are not able to enable the maintenance page.
    $response = $this->postRpc($enabled_request);
    $this->assertSame(401, $response->getStatusCode());

    // Assign correct permission and login.
    $account = $this->createUser(['administer site configuration'], NULL, TRUE);

    // Retry request with basic auth.
    $response = $this->postRpc($enabled_request, $account);
    $this->assertSame(200, $response->getStatusCode());
    $parsed_body = Json::decode($response->getBody());
    $expected = [
      'jsonrpc' => '2.0',
      'id' => 'maintenance_mode_enabled',
      'result' => 'enabled',
    ];
    $this->assertEquals($expected, $parsed_body);

    // Asssert maintenance mode is enabled.
    $this->drupalGet('/jsonrpc');
    $this->assertEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());

    // Send request to disable maintenance mode.
    $disabled_request = [
      'jsonrpc' => '2.0',
      'method' => 'maintenance_mode.isEnabled',
      'params' => [
        'enabled' => FALSE,
      ],
      'id' => 'maintenance_mode_disabled',
    ];

    $response = $this->postRpc($disabled_request, $account);
    $this->assertSame(200, $response->getStatusCode());
    $parsed_body = Json::decode($response->getBody());
    $expected = [
      'jsonrpc' => '2.0',
      'id' => 'maintenance_mode_disabled',
      'result' => 'disabled',
    ];
    $this->assertEquals($expected, $parsed_body);

    // Asssert maintenance mode is disabled.
    $this->drupalGet('/jsonrpc');
    $this->assertNotEquals('Site under maintenance', $this->cssSelect('main h1')[0]->getText());
  }

}
