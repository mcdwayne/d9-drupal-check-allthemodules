<?php

namespace Drupal\Tests\simple_integrations\Functional;

use Drupal\simple_integrations\ConnectionClient;
use Drupal\simple_integrations\Exception\DebugModeDisabledException;
use Drupal\simple_integrations\Exception\EmptyDebugMessageException;
use Drupal\simple_integrations\Exception\IntegrationInactiveException;

/**
 * Test the basic integration functionality.
 *
 * In this test, we ensure that the settings can be changed successfully. For
 * example:
 *  - Ensure the integration values are correct.
 *  - Ensure the connection values are correct.
 *  - Ensure requests can be made when the integration is active.
 *  - Ensure requests can't be made when the integration is inactive.
 *  - Ensure that correctly-formatted messages can be logged when debug mode is
 *    enabled.
 *  - Ensure that incorrectly-formatted messages can't be logged when debug
 *    mode is enabled.
 *  - Ensure that correctly-formatted messages can't be logged when debug mode
 *    is disabled.
 *
 * @group simple_integrations
 */
class BasicIntegrationFunctionalityTest extends SimpleIntegrationsTestBase {

  /**
   * Setup.
   */
  public function setUp() {
    parent::setUp();

    // Create a new integration.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('integration');
    $integration = $entity_storage->create($this->getDefaultConfig());
    $integration->save();
    $this->integration = $integration;

    // Create a connection client.
    $connection = new ConnectionClient();
    $connection->setIntegration($this->integration);
    $connection->configure();
    $this->connection = $connection;
  }

  /**
   * Test the integration values.
   *
   * @see SimpleIntegrationsTestBase::getDefaultConfig()
   */
  public function testIntegrationValues() {
    $this->assertEquals(TRUE, $this->integration->isActive());
    $this->assertEquals(FALSE, $this->integration->isDebugMode());
  }

  /**
   * Test the connection values.
   *
   * @see SimpleIntegrationsTestBase::getDefaultConfig()
   */
  public function testConnectionValues() {
    $this->assertEquals('none', $this->connection->getAuthType());
    $this->assertEquals('', $this->connection->getAuthUser());
    $this->assertEquals('', $this->connection->getAuthKey());
    $this->assertEquals('https://httpbin.org/get', $this->connection->getRequestEndPoint());
  }

  /**
   * Given an integration, test that a connection can be made.
   */
  public function testActiveIntegrationConnection() {
    $response = $this->connection->get(
      $this->connection->getRequestEndPoint(),
      $this->connection->getRequestConfig()
    );
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Given an inactive integration, test that no connection can be made.
   */
  public function testInactiveIntegrationConnection() {
    $this->expectException(IntegrationInactiveException::class);
    $this->integration->set('active', FALSE);
    $this->integration->save();

    $this->integration->performConnectionTest($this->connection);
  }

  /**
   * Given an integration, test that a valid debug message can be created.
   *
   * @dataProvider debugMessageGoodDataProvider
   */
  public function testGoodDebugMessages($message, $type) {
    $this->integration->set('active', TRUE);
    $this->integration->set('debug_mode', TRUE);

    $this->integration->logDebugMessage($message, $type);
  }

  /**
   * Given an empty debug message, ensure no debug message is created.
   *
   * @dataProvider debugMessageBadDataProvider()
   *
   * @see debugMessageBadDataProvider()
   */
  public function testEmptyDebugMessages($message, $type) {
    $this->expectException(EmptyDebugMessageException::class);
    $this->integration->logDebugMessage($message, $type);
  }

  /**
   * Given an integration, ensure no message is created when not in debug mode.
   *
   * @dataProvider debugMessageGoodDataProvider()
   *
   * @see debugMessageGoodDataProvider()
   */
  public function testDebugModeDisabled($message, $type) {
    $this->expectException(DebugModeDisabledException::class);
    $this->integration->set('debug_mode', FALSE);
    $this->integration->logDebugMessage($message, $type);
  }

  /**
   * Valid debug message data provider.
   *
   * @see testGoodDebugMessages()
   * @see testDebugModeDisabled()
   */
  public function debugMessageGoodDataProvider() {
    return [
      ['Test debug message', 'notice'],
      ['There was an error', 'error'],
    ];
  }

  /**
   * Invalid debug message data provider.
   *
   * @see testEmptyDebugMessages()
   */
  public function debugMessageBadDataProvider() {
    return [
      ['', 'error'],
      [[], 'error'],
    ];
  }

}
