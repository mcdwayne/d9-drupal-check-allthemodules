<?php

namespace Drupal\Tests\sourcepoint\Unit\Api;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\sourcepoint\Api\AbstractEndpoint
 *
 * @group sourcepoint
 */
class AbstractEndpointTest extends UnitTestCase {

  /**
   * @covers ::getPath
   * @covers ::setPath
   */
  public function testPath() {
    $endpoint = $this->getMockBuilder('Drupal\sourcepoint\Api\AbstractEndpoint')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $endpoint->setPath('path/to/file');
    $this->assertEquals('path/to/file', $endpoint->getPath());
  }

}
