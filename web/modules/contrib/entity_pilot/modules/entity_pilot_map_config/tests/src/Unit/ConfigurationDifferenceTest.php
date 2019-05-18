<?php

namespace Drupal\Tests\entity_pilot_map_config\Unit;

use Drupal\entity_pilot_map_config\ConfigurationDifference;
use Drupal\Tests\UnitTestCase;

/**
 * Tests configuration difference value object.
 *
 * @group entity_pilot
 *
 * @coversDefaultClass \Drupal\entity_pilot_map_config\ConfigurationDifference
 */
class ConfigurationDifferenceTest extends UnitTestCase {

  /**
   * Tests hasMissingFields().
   *
   * @covers ::hasMissingFields
   */
  public function testHasMissingFields() {
    $difference = new ConfigurationDifference(['foo' => 'bar']);
    $this->assertTrue($difference->hasMissingFields());
  }

  /**
   * Tests hasMissingFields().
   *
   * @covers ::hasMissingBundles
   */
  public function testHasMissingBundles() {
    $difference = new ConfigurationDifference(['foo' => 'bar'], ['hoo' => 'haa']);
    $this->assertTrue($difference->hasMissingBundles());
  }

  /**
   * Tests hasMissingFields().
   *
   * @covers ::hasMissingEntityTypes
   */
  public function testHasMissingEntityTypes() {
    $difference = new ConfigurationDifference(['foo' => 'bar'], [], ['entity_test']);
    $this->assertTrue($difference->hasMissingEntityTypes());
  }

  /**
   * Tests requiresMapping().
   *
   * @covers ::requiresMapping
   */
  public function testRequiresMapping() {
    $difference = new ConfigurationDifference(['foo' => 'bar'], [], ['entity_test']);
    $this->assertTrue($difference->requiresMapping());
  }

}
