<?php

namespace Drupal\Tests\elastic_search\Unit\Plugin\ElasticAbstractField;

use Drupal\elastic_search\Plugin\ElasticAbstractField\ReverseGeo;

/**
 * Class ReverseGeoTest
 *
 * @package Drupal\Tests\elastic_search\Plugin\ElasticAbstractField
 */
class ReverseGeoTest extends ElasticAbstractFieldTestBase {

  /**
   * Test if the getAbstractFields returns the valid array keys.
   */
  public function testFieldsContainEssentialKeys() {
    $plugin = new ReverseGeo([], 'plugin_id', []);

    $fields = $plugin->getAbstractFields();

    $this->assertEssentialFieldsAreSet($fields);
  }

  /**
   * Test isNested function to return true.
   */
  public function testIsNestedReturnsTrue() {
    $plugin = new ReverseGeo([], 'plugin_id', []);
    $this->assertTrue($plugin->isNested());
  }

}
