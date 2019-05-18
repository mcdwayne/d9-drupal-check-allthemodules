<?php

namespace Drupal\Tests\elastic_search\Unit\Plugin\ElasticAbstractField;

use Drupal\elastic_search\Plugin\ElasticAbstractField\RangeInteger;

/**
 * Class RangeIntegerTest
 *
 * @package Drupal\Tests\elastic_search\Plugin\ElasticAbstractField
 */
class RangeIntegerTest extends ElasticAbstractFieldTestBase {

  /**
   * Test if the getAbstractFields returns the valid array keys.
   */
  public function testFieldsContainEssentialKeys() {
    $plugin = new RangeInteger([], 'plugin_id', []);

    $fields = $plugin->getAbstractFields();

    $this->assertEssentialFieldsAreSet($fields);
  }

  /**
   * Test isNested function to return true.
   */
  public function testIsNestedReturnsTrue() {
    $plugin = new RangeInteger([], 'plugin_id', []);
    $this->assertTrue($plugin->isNested());
  }

}
