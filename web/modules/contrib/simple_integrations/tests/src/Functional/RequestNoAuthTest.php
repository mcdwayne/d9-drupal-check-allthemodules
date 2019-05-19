<?php

namespace Drupal\Tests\simple_integrations\Functional;

/**
 * Test a request with no auth set.
 *
 * This set of tests performs the most basic kind of connection request: a
 * simple GET request.
 *
 * @group simple_integrations
 */
class RequestNoAuthTest extends SimpleIntegrationsTestBase {

  /**
   * Setup.
   */
  public function setUp() {
    parent::setUp();
    $this->setUpConnection([]);
  }

  /**
   * Test that a connection can be made.
   */
  public function testConnection() {
    $this->integration->performConnectionTest($this->connection);
    $this->assertSession()->statusCodeEquals(200);
  }

}
