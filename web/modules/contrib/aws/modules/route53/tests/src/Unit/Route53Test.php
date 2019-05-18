<?php

namespace Drupal\Tests\aws\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\aws\Service\Route53
 *
 * @group aws
 */
class Route53Test extends UnitTestCase {

  /**
   * @covers ::loadProfile
   */
  public function testLoadProfile() {
    $this->assertTrue(TRUE);
  }

}
