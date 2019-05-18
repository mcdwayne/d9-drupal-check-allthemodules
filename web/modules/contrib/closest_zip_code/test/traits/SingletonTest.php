<?php

namespace Drupal\closest_zip_code\Tests;

use Drupal\closest_zip_code\traits\Singleton;
use PHPUnit\Framework\TestCase;

class DummyClassUsesSingleton {
  use Singleton;
}

/**
 * Test CommonUtilities.
 *
 * @group myproject
 */
class SingletonTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $this->assertTrue(DummyClassUsesSingleton::instance() === DummyClassUsesSingleton::instance());
  }

}
