<?php

namespace Drupal\range\Tests;

/**
 * Tests range field validation.
 *
 * @group range
 */
class RangeFieldValidationTest extends RangeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'range', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType();
    $this->drupalLogin($this->drupalCreateUser(["create $this->bundle content"]));
  }

  /**
   * Tests range field constraints.
   *
   * @param string $field_type
   *   Range field type. Could be one of the following values: range_integer,
   *   range_float or range_decimal.
   */
  protected function rangeTestConstraints($field_type) {
    $field_name = $this->getTestFieldName($field_type);
    $path = "node/add/$this->bundle";
    $validation_error_1 = 'Both range values (FROM and TO) are required.';
    $validation_error_2 = 'The FROM value is higher than the TO value.';

    $items = [
      [
        'edit' => [
          "{$field_name}[0][from]" => 10,
          "{$field_name}[0][to]" => '',
        ],
        'error' => $validation_error_1,
      ],
      [
        'edit' => [
          "{$field_name}[0][from]" => '',
          "{$field_name}[0][to]" => 10,
        ],
        'error' => $validation_error_1,
      ],
      [
        'edit' => [
          "{$field_name}[0][from]" => 10,
          "{$field_name}[0][to]" => 5,
        ],
        'error' => $validation_error_2,
      ],
    ];
    foreach ($items as $item) {
      $this->drupalPostForm($path, $item['edit'], t('Save'));
      $this->assertText($item['error']);
    }

    $valid_edit = [
      "{$field_name}[0][from]" => 5,
      "{$field_name}[0][to]" => 10,
    ];

    $this->drupalPostForm($path, $valid_edit, t('Save'));
    $this->assertNoText($validation_error_1);
    $this->assertNoText($validation_error_2);
  }

  /**
   * Tests range field validation.
   */
  public function testRangeValidation() {
    foreach (['range_integer', 'range_float', 'range_decimal'] as $field_type) {
      $this->createRangeField($field_type);
      $this->rangeTestConstraints($field_type);
      $this->deleteRangeField();
    }
  }

}
