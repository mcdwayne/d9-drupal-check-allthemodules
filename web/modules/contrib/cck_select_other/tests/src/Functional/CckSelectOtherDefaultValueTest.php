<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests default value behavior in select other field.
 *
 * @group cck_select_other
 */
class CckSelectOtherDefaultValueTest extends CckSelectOtherTestBase {

  /**
   * Asserts that default value is selected.
   */
  public function testDefaultValue() {
    $options = $this->createOptions();
    list($default_value, $label) = $this->getRandomOption($options);
    $storage_values = [
      'settings' => ['allowed_values' => $options],
      'cardinality' => 1,
    ];
    $config_values = [
      'required' => 0,
      'default_value' => [
        ['value' => $default_value],
      ],
    ];
    $field = $this->createSelectOtherListField('list_string', $storage_values, $config_values);
    $field_name = $field->getName();

    // Log in and try to create content with an empty value.
    $this->drupalLogin($this->webUser);

    $field_id = 'edit-' . $field_name . '-0-select-other-list';
    $this->drupalGet('/node/add/' . $this->contentType->id());
    $this->assertOptionSelected($field_id, $default_value, 'Default value selected is ' . $default_value);
  }

}
