<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests that two select other fields work on the same node.
 *
 * @group cck_select_other
 */
class CckSelectOtherMultipleFieldTest extends CckSelectOtherTestBase {

  /**
   * Field configuration of the first field.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldStorageConfig
   */
  protected $firstField;

  /**
   * Field configuration of the second field.
   *
   * @var \Drupal\Core\Entity\EntityInterface|\Drupal\field\Entity\FieldStorageConfig
   */
  protected $secondField;

  /**
   * Associative array of options for the select list.
   *
   * @var array
   */
  protected $firstOptions;

  /**
   * Associative array of options for the select list.
   *
   * @var array
   */
  protected $secondOptions;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the first field.
    $this->firstOptions = $this->createOptions();

    $field_info = [
      'settings' => [
        'allowed_values' => $this->firstOptions,
      ],
      'cardinality' => 1,
    ];
    $config_info = ['required' => 0];

    $this->firstField = $this->createSelectOtherListField('list_string', $field_info, $config_info);

    // Create the second field.
    $this->secondOptions = $this->createOptions();

    $field_info['settings']['allowed_values'] = $this->secondOptions;

    $this->secondField = $this->createSelectOtherListField('list_string', $field_info, $config_info);
  }

  /**
   * Asserts that field one and field two have unique values.
   */
  public function testMultipleFields() {
    $field_one = $this->firstField->getName();
    $field_two = $this->secondField->getName();

    // Login as content creator.
    $this->drupalLogin($this->webUser);

    list($value_one, $label) = $this->getRandomOption($this->firstOptions);
    $value_two = $this->getRandomGenerator()->word(15);

    $edit = [
      'title[0][value]' => $this->randomString(25),
      $field_one . '[0][select_other_list]' => $value_one,
      $field_two . '[0][select_other_list]' => 'other',
      $field_two . '[0][select_other_text_input]' => $value_two,
    ];
    $this->drupalPostForm('/node/add/' . $this->contentType->id(), $edit, 'Save');
    $this->assertSession()
      ->responseContains('<div class="field__item">' . $this->firstOptions[$value_one] . '</div>');
    $this->assertSession()
      ->responseContains('<div class="field__item">' . $value_two . '</div>');
  }

}
