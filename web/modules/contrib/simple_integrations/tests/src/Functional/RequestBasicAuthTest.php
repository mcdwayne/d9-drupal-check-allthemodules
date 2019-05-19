<?php

namespace Drupal\Tests\simple_integrations\Functional;

use GuzzleHttp\Exception\ClientException;

/**
 * Test a request with basic auth.
 *
 * @group simple_integrations
 */
class RequestBasicAuthTest extends SimpleIntegrationsTestBase {

  /**
   * Setup.
   */
  public function setUp() {
    parent::setUp();

    // Create an integration with basic auth details.
    $setup_config = [
      'id' => 'test_integration_basic_auth',
      'label' => 'Test integration - basic auth',
      'external_end_point' => 'https://httpbin.org/basic-auth/user/passwd',
      'auth_type' => 'basic_auth',
      'auth_user' => 'user',
      'auth_key' => 'passwd',
    ];
    $this->setUpConnection($setup_config);
  }

  /**
   * Test connection status.
   */
  public function testConnection() {
    $response = $this->connection->get(
      $this->connection->getRequestEndPoint(),
      $this->connection->getRequestConfig()
    );
    $this->assertEquals(200, $response->getStatusCode());

    $this->expectException(ClientException::class);
    $response = $this->connection->get(
      $this->connection->getRequestEndPoint(),
      $this->connection->getRequestConfig()
    );
    $this->assertEquals(401, $response->getStatusCode());
  }

}
