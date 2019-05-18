<?php

namespace Drupal\Tests\fapi_validation\Unit\Filters;

use Drupal\Tests\UnitTestCase;
use Drupal\fapi_validation\Plugin\FapiValidationFilter\HtmlEntitiesFilter;

/**
 * Tests filter HtmlEntitiesFilter
 *
 * @group fapi_validation
 * @group fapi_validation_filters
 */
class HtmlEntitiesFilterTest extends UnitTestCase {

  /**
   * Testign lowercasing string.
   */
  public function testValidString() {
    $plugin = new HtmlEntitiesFilter();
    $this->assertEquals('&lt;i&gt;TesT&lt;/i&gt;', $plugin->filter('<i>TesT</i>'));
  }

}
