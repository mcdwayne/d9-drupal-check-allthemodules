<?php

namespace Drupal\Tests\sourcepoint\Unit\Api;

use Drupal\sourcepoint\Api\EndpointManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sourcepoint\Api\EndpointManager
 *
 * @group sourcepoint
 */
class EndpointManagerTest extends UnitTestCase {

  /**
   * @covers ::addEndpoint
   * @covers ::getEndpoint
   */
  public function testGetEndpoint() {
    // Mock an endpoint to add to manager.
    $endpoint = $this->getMockBuilder('Drupal\sourcepoint\Api\AbstractEndpoint')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $endpoint->method('getName')->willReturn('my_endpoint');

    $endpoint_manager = new EndpointManager();
    $endpoint_manager->addEndpoint($endpoint);
    $this->assertEquals($endpoint, $endpoint_manager->getEndpoint('my_endpoint'));
  }

}
