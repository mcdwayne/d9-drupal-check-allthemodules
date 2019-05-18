<?php

namespace Drupal\Tests\elastic_search\Unit\Plugin\ElasticAbstractField;

use Drupal\Tests\UnitTestCase;

abstract class ElasticAbstractFieldTestBase extends UnitTestCase {

  /**
   * @param array|mixed $fields
   */
  public function assertEssentialFieldsAreSet($fields) {
    $this->assertNotEmpty($fields);
    foreach ($fields as $field) {
      $this->assertArrayHasKey('nested', $field);
      $this->assertArrayHasKey('map', $field);
      $this->assertNotEmpty($field['map']);

      // Search in sub-sets.
      if (is_array($field) && isset($field['map'])) {
        foreach ($field['map'] as $options) {
          $this->assertArrayHasKey('type', $options);
        }
      }
    }
  }

}
