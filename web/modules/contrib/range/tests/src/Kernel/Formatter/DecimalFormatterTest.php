<?php

namespace Drupal\Tests\range\Kernel\Formatter;

/**
 * Tests the decimal formatter.
 *
 * @group range
 */
class DecimalFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fieldType = 'range_float';
    $this->displayType = 'range_decimal';

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
        1234.5678,
        7856.4321,
        '1234.57-7856.43',
      ],
      [
        ['scale' => 3],
        1234.5678,
        7856.4321,
        '1234.568-7856.432',
      ],
      [
        ['thousand_separator' => ' '],
        1234.5678,
        7856.4321,
        '1 234.57-7 856.43',
      ],
      [
        ['decimal_separator' => ','],
        1234.5678,
        7856.4321,
        '1234,57-7856,43',
      ],
      [
        ['range_separator' => '|'],
        1234.5678,
        7856.4321,
        '1234.57|7856.43',
      ],
      [
        ['from_prefix_suffix' => TRUE],
        1234.5678,
        7856.4321,
        'from_prefix1234.57from_suffix-7856.43',
      ],
      [
        ['to_prefix_suffix' => TRUE],
        1234.5678,
        7856.4321,
        '1234.57-to_prefix7856.43to_suffix',
      ],
      [
        ['from_prefix_suffix' => TRUE, 'to_prefix_suffix' => TRUE],
        1234.5678,
        7856.4321,
        'from_prefix1234.57from_suffix-to_prefix7856.43to_suffix',
      ],
      // Test combined values.
      [
        [],
        1234.5678,
        1234.5678,
        '1234.57',
      ],
      [
        ['range_combine' => FALSE],
        1234.5678,
        1234.5678,
        '1234.57-1234.57',
      ],
      [
        ['scale' => 3],
        1234.5678,
        1234.5678,
        '1234.568',
      ],
      [
        ['thousand_separator' => ' '],
        1234.5678,
        1234.5678,
        '1 234.57',
      ],
      [
        ['decimal_separator' => ','],
        1234.5678,
        1234.5678,
        '1234,57',
      ],
      [
        ['from_prefix_suffix' => TRUE],
        1234.5678,
        1234.5678,
        'from_prefix1234.57',
      ],
      [
        ['to_prefix_suffix' => TRUE],
        1234.5678,
        1234.5678,
        '1234.57to_suffix',
      ],
      [
        ['from_prefix_suffix' => TRUE, 'to_prefix_suffix' => TRUE],
        1234.5678,
        1234.5678,
        'from_prefix1234.57to_suffix',
      ],
    ];
  }

}
