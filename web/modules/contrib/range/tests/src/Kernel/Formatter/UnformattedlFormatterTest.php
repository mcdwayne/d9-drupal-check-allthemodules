<?php

namespace Drupal\Tests\range\Kernel\Formatter;

/**
 * Tests the unformatted formatter.
 *
 * @group range
 */
class UnformattedlFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fieldType = 'range_float';
    $this->displayType = 'range_unformatted';

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  public function formatterDataProvider() {
    return [
      [[], 1234.5678, 7856.4321, '1234.5678-7856.4321'],
      [['range_separator' => '|'], 1234.5678, 7856.4321, '1234.5678|7856.4321'],
    ];
  }

}
