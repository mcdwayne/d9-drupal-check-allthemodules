<?php

namespace Drupal\Tests\fapi_validation\Unit\Filters;

use Drupal\Tests\UnitTestCase;
use Drupal\fapi_validation\Plugin\FapiValidationFilter\StripTagsFilter;

/**
 * Tests filter StripTagsFilter
 *
 * @group fapi_validation
 * @group fapi_validation_filters
 */
class StripTagsFilterTest extends UnitTestCase {

  /**
   * Testign lowercasing string.
   */
  public function testValidString() {
    $plugin = new StripTagsFilter;

    $this->assertEquals('test', $plugin->filter('<strong>test</strong>'));
    $this->assertEquals('test', $plugin->filter('test'));
  }

}
