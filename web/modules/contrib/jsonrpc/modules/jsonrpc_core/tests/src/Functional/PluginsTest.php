<?php

namespace Drupal\Tests\jsonrpc_core\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\jsonrpc\Functional\JsonRpcTestBase;

/**
 * Test turning the maintenance mode on or off using JSON RPC.
 *
 * @group jsonrpc
 */
class PluginsTest extends JsonRpcTestBase {

  protected static $modules = [
    'filter',
    'jsonrpc',
    'jsonrpc_core',
    'basic_auth',
    'serialization',
  ];

  /**
   * Tests the plugin list.
   */
  public function testPlugins() {
    // 1. Test without a pager.
    $rpc_request = [
      'jsonrpc' => '2.0',
      'method' => 'plugins.list',
      'id' => 1,
      'params' => [
        'service' => 'plugin.manager.filter',
      ],
    ];

    // Assert that anonymous users are not able to get plugin information.
    $response = $this->getRpc($rpc_request);
    $this->assertSame(401, $response->getStatusCode());

    // Assign correct permission and login.
    $account = $this->createUser(['administer site configuration'], NULL, TRUE);

    // Retry request with auth.
    $response = $this->getRpc($rpc_request, $account);
    $this->assertSame(200, $response->getStatusCode());
    $parsed_body = Json::decode($response->getBody());
    $this->assertArrayHasKey('result', $parsed_body, 'Could not find results');
    $this->assertNotEmpty($parsed_body['result'], 'No filter plugins returned');
    $first_result = array_keys($parsed_body['result'])[0];

    // 2. Test with a pager.
    $rpc_request = [
      'jsonrpc' => '2.0',
      'method' => 'plugins.list',
      'id' => 1,
      'params' => [
        'service' => 'plugin.manager.filter',
        'page' => ['limit' => 2, 'offset' => 1],
      ],
    ];
    $response = $this->getRpc($rpc_request, $account);
    $this->assertSame(200, $response->getStatusCode());
    $parsed_body = Json::decode($response->getBody());
    $this->assertCount(2, $parsed_body['result']);
    $this->assertNotEquals($first_result, array_keys($parsed_body['result'])[0]);

    // 3. Test without service.
    $rpc_request = [
      'jsonrpc' => '2.0',
      'method' => 'plugins.list',
      'id' => 1,
      'params' => [
        'page' => ['limit' => 2, 'offset' => 1],
      ],
    ];
    $response = $this->getRpc($rpc_request, $account);
    $this->assertSame(400, $response->getStatusCode());
    $parsed_body = Json::decode($response->getBody());
    $expected = [
      'jsonrpc' => '2.0',
      'id' => 1,
      'error' => [
        'code' => -32602,
        'message' => 'Invalid Params',
        'data' => 'Missing required parameter: service',
      ],
    ];
    $this->assertEquals($expected, $parsed_body);
  }

}
