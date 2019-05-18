<?php

namespace Drupal\closest_zip_code\Tests;

use Drupal\closest_zip_code\traits\CommonUtilities;
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
