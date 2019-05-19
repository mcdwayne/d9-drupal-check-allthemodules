<?php

namespace Drupal\Test\social_migration\Unit\process;

use Drupal\migrate\Row;
use Drupal\social_migration\Plugin\migrate\process\Permalink;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the Permalink process plugin.
 *
 * @group social_migration
 * @coversDefaultClass \Drupal\social_migration\Plugin\migrate\process\Permalink
 */
class PermalinkTest extends MigrateProcessTestCase {

  /**
   * Test the transform() method.
   *
   * @dataProvider provideTestCases
   */
  public function testTransform($property_name, $id, $expected) {
    $source = [
      'id' => $id,
    ];
    $sourceIds = ['id' => ['type' => 'string']];
    $row = new Row($source, $sourceIds);

    $config = [
      'property_name' => $property_name,
    ];
    $plugin = new Permalink($config, 'permalink', []);
    $actual = $plugin->transform('', $this->migrateExecutable, $row, 'destinationproperty');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider.
   *
   * @return array
   *   The test cases to run.
   */
  public function provideTestCases() {
    return [
      'test property name' => [
        'property_name' => 'easternstandard',
        'id' => '12345',
        'expected' => 'https://twitter.com/easternstandard/status/12345',
      ],
    ];
  }

}
