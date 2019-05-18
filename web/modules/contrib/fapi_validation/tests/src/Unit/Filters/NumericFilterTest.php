<?php

namespace Drupal\Tests\fapi_validation\Unit\Filters;

use Drupal\Tests\UnitTestCase;
use Drupal\fapi_validation\Plugin\FapiValidationFilter\NumericFilter;

/**
 * Tests filter NumericFilter
 *
 * @group fapi_validation
 * @group fapi_validation_filters
 */
class NumericFilterTest extends UnitTestCase {

  /**
   * Testign lowercasing string.
   */
  public function testValidString() {
    $plugin = new NumericFilter();
    $this->assertEquals('12345', $plugin->filter('12345'));
    $this->assertEquals('12345', $plugin->filter('aa12aa345aa'));
    $this->assertEquals('', $plugin->filter('abcdef'));
  }

}
