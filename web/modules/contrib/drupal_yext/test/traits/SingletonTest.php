<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\traits\Singleton;
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
