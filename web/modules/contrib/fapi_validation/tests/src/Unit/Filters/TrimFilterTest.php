<?php

namespace Drupal\Tests\fapi_validation\Unit\Filters;

use Drupal\Tests\UnitTestCase;
use Drupal\fapi_validation\Plugin\FapiValidationFilter\TrimFilter;

/**
 * Tests filter TrimFilter
 *
 * @group fapi_validation
 * @group fapi_validation_filters
 */
class TrimFilterTest extends UnitTestCase {

  /**
   * Testing valid string.
   */
  public function testValidString() {
    $plugin = new TrimFilter();
    $this->assertEquals('test', $plugin->filter('   test    '));
  }

}
