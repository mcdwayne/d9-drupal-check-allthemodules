<?php

namespace Drupal\Tests\social_migration\Unit\process;

use Drupal\migrate\Row;
use Drupal\social_migration\Plugin\migrate\process\Coalesce;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the Coalesce process plugin.
 *
 * @group social_migration
 * @coversDefaultClass \Drupal\social_migration\Plugin\migrate\process\Coalesce
 */
class CoalesceTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // In this case, we need to supply an actual row, not the mocked version.
    // So define a row, and pass it to each test case.
    $source = [
      'id' => '1',
      'null_field' => NULL,
      'blank_field' => '',
      'full_field_1' => 'foo',
      'full_field_2' => 'bar',
      'fields' => [
        ['name' => 'id', 'selector' => 'id'],
        ['name' => 'null_field', 'selector' => 'null_field'],
        ['name' => 'blank_field', 'selector' => 'blank_field'],
        ['name' => 'full_field_1', 'selector' => 'full_field_1'],
        ['name' => 'full_field_2', 'selector' => 'full_field_2'],
      ],
    ];
    $sourceIds = ['id' => ['type' => 'string']];
    $this->row = new Row($source, $sourceIds);
  }

  /**
   * Test the transform() method.
   *
   * @dataProvider provideTestCases
   */
  public function testTransform($inputs, $expected) {
    $plugin = new Coalesce($inputs, 'coalesce', []);
    $actual = $plugin->transform('', $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider.
   *
   * @return array
   *   The test cases to run.
   */
  public function provideTestCases() {
    // In the return, each test case input references a field name, NOT an
    // actual scalar value.
    return [
      'test first value acceptable' => [
        'inputs' => [
          'source' => ['full_field_1', 'null_field'],
          'default_value' => 'default',
        ],
        'expected' => 'foo',
      ],
      'test null is skipped' => [
        'inputs' => [
          'source' => ['null_field', 'full_field_1'],
          'default_value' => 'default',
        ],
        'expected' => 'foo',
      ],
      'test blank string is skipped' => [
        'inputs' => [
          'source' => ['blank_field', 'full_field_1'],
          'default_value' => 'default',
        ],
        'expected' => 'foo',
      ],
      'test first of several acceptable values is returned' => [
        'inputs' => [
          'source' => ['blank_field', 'full_field_1', 'full_field_2'],
          'default_value' => 'default',
        ],
        'expected' => 'foo',
      ],
      'test default is returned if nothing is acceptable' => [
        'inputs' => [
          'source' => ['blank_field', 'null_field'],
          'default_value' => 'default',
        ],
        'expected' => 'default',
      ],
      'test NULL is returned if nothing is acceptable and no default is specified' => [
        'inputs' => [
          'source' => ['blank_field', 'null_field'],
          'default_value' => NULL,
        ],
        'expected' => NULL,
      ],
    ];
  }

}
