<?php

namespace Drupal\drupal_yext\Tests;

use Drupal\drupal_yext\traits\CommonUtilities;
use PHPUnit\Framework\TestCase;

class DummyClassUsesCommonUtilities {
  use CommonUtilities;
}

/**
 * Test CommonUtilities.
 *
 * @group myproject
 */
class CommonUtilitiesTest extends TestCase {

  /**
   * Smoke test.
   */
  public function testSmoke() {
    $object = new DummyClassUsesCommonUtilities();

    $this->assertTrue(is_object($object));
  }

}
