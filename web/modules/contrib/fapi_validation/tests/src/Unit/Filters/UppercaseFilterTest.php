<?php

namespace Drupal\Tests\fapi_validation\Unit\Filters;

use Drupal\Tests\UnitTestCase;
use Drupal\fapi_validation\Plugin\FapiValidationFilter\UppercaseFilter;

/**
 * Tests filter UppercaseFilter
 *
 * @group fapi_validation
 * @group fapi_validation_filters
 */
class UppercaseFilterTest extends UnitTestCase {

  /**
   * Testing uppercasing.
   */
  public function testValidString() {
    $plugin = new UppercaseFilter();
    $this->assertEquals('TEST', $plugin->filter('test'));
  }

}
