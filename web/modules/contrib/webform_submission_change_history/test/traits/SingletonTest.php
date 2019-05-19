<?php

namespace Drupal\webform_submission_change_history\Tests;

use Drupal\webform_submission_change_history\traits\Singleton;
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
