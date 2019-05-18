<?php

namespace Drupal\Tests\range\Kernel\Formatter;

/**
 * Tests the integer formatter.
 *
 * @group range
 */
class IntegerFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fieldType = 'range_integer';
    $this->displayType = 'range_integer';

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function formatterDataProvider() {
    return [
      // Test separate values.
      [
        [],
        1234,
        5678,
        '1234-5678',
      ],
      [
        ['thousand_separator' => ' '],
        1234,
        5678,
        '1 234-5 678',
      ],
      [
        ['range_separator' => '|'],
        1234,
        5678,
        '1234|5678',
      ],
      [
        ['from_prefix_suffix' => TRUE],
        1234,
        5678,
        'from_prefix1234from_suffix-5678',
      ],
      [
        ['to_prefix_suffix' => TRUE],
        1234,
        5678,
        '1234-to_prefix5678to_suffix',
      ],
      [
        ['from_prefix_suffix' => TRUE, 'to_prefix_suffix' => TRUE],
        1234,
        5678,
        'from_prefix1234from_suffix-to_prefix5678to_suffix',
      ],
      // Test combined values.
      [
        [],
        1234,
        1234,
        '1234',
      ],
      [
        ['range_combine' => FALSE],
        1234,
        1234,
        '1234-1234',
      ],
      [
        ['thousand_separator' => ' '],
        1234,
        1234,
        '1 234',
      ],
      [
        ['from_prefix_suffix' => TRUE],
        1234,
        1234,
        'from_prefix1234',
      ],
      [
        ['to_prefix_suffix' => TRUE],
        1234,
        1234,
        '1234to_suffix',
      ],
      [
        ['from_prefix_suffix' => TRUE, 'to_prefix_suffix' => TRUE],
        1234,
        1234,
        'from_prefix1234to_suffix',
      ],
    ];
  }

}
