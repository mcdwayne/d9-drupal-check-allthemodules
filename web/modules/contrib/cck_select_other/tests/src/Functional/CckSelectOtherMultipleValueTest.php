<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests that multiple values are saved properly.
 *
 * @group cck_select_other
 */
class CckSelectOtherMultipleValueTest extends CckSelectOtherTestBase {

  /**
   * Test field validation for required attribute.
   */
  public function testMultipleValues() {
    $options = $this->createOptions();

    $field_info = [
      'settings' => [
        'allowed_values' => $options,
      ],
      'cardinality' => 3,
    ];
    $config_info = ['required' => 0];
    $field = $this->createSelectOtherListField('list_string', $field_info, $config_info);
    $field_name = $field->getName();

    // Login as content creator.
    $this->drupalLogin($this->webUser);

    list($value, $label) = $this->getRandomOption($options);
    $other_value = $this->getRandomGenerator()->word(15);
    $third_value = $this->getRandomGenerator()->word(5);

    $edit = [
      'title[0][value]' => $this->randomString(25),
      $field_name . '[0][select_other_list]' => 'other',
      $field_name . '[0][select_other_text_input]' => $other_value,
      $field_name . '[1][select_other_list]' => $value,
      $field_name . '[1][select_other_text_input]' => '',
      $field_name . '[2][select_other_list]' => 'other',
      $field_name . '[2][select_other_text_input]' => $third_value,
    ];
    $this->drupalPostForm('/node/add/' . $this->contentType->id(), $edit, 'Save');
    $this->assertSession()
      ->responseContains('<div class="field__item">' . $options[$value] . '</div>');
    $this->assertSession()
      ->responseContains('<div class="field__item">' . $other_value . '</div>');
    $this->assertSession()
      ->responseContains('<div class="field__item">' . $third_value . '</div>');

    $this->drupalGet('/node/1/edit');
    $this->assertOptionSelected('edit-' . $field_name . '-0-select-other-list', 'other', 'Select list #0 other selected');
    $this->assertOptionSelected('edit-' . $field_name . '-1-select-other-list', $value, 'Select list #1 other selected');
    $this->assertOptionSelected('edit-' . $field_name . '-2-select-other-list', 'other', 'Select list #2 other selected');

    $edit = [
      $field_name . '[2][select_other_list]' => '_none',
      $field_name . '[2][select_other_text_input]' => '',
    ];
    $this->drupalPostForm('/node/1/edit', $edit, 'Save');

    // List fields re-arrange themselves in alphabetical order, and so it is not
    // possible to assert the exact value because Drupal. Instead there should
    // only be 2 field items listed.
    $this->assertSession()
      ->elementsCount(
        'xpath',
        '//div[contains(@class, "field--name-' . $field_name . '")]/div[@class="field__items"]/div[@class="field__item"]',
        2);
  }

}
