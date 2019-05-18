<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests a basic text field.
 *
 * @group cck_select_other
 */
class CckSelectOtherFieldTypeTest extends CckSelectOtherTestBase {

  /**
   * Asserts that a user can save regular and other field values.
   *
   * @param string $field_type
   *   The field type plugin ID.
   * @param mixed $other_value
   *   The other value to set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   *
   * @dataProvider typeProvider
   */
  public function testField($field_type, $other_value) {
    $options = $this->createOptions(5, $field_type);
    $storage_values = [
      'settings' => ['allowed_values' => $options],
      'cardinality' => 1,
    ];
    $config_values = ['required' => 0];
    $field = $this->createSelectOtherListField($field_type, $storage_values, $config_values);
    $this->assertEqual(5, count($field->getSettings()['allowed_values']));
    $field_name = $field->getName();

    // Create a new node with other value after logging in.
    $this->drupalLogin($this->webUser);
    $edit = [
      'title[0][value]' => $this->randomString(25),
      $field_name . '[0][select_other_list]' => 'other',
      $field_name . '[0][select_other_text_input]' => $other_value,
    ];
    $this->drupalPostForm('/node/add/' . $this->contentType->id(), $edit, 'Save');
    $this->assertSession()
      ->elementTextContains('css', '.field__item', $other_value);

    // Edit node with other value to list value.
    list($value, $label) = $this->getRandomOption($options);
    $edit = [
      $field_name . '[0][select_other_list]' => $value,
      $field_name . '[0][select_other_text_input]' => '',
    ];
    $this->drupalPostForm('/node/1/edit', $edit, 'Save');
    $this->assertSession()
      ->elementTextContains('css', '.field__item', $label);
  }

  /**
   * Returns test arguments.
   *
   * @return array
   *   An array of test arguments.
   */
  public function typeProvider() {
    return [
      'text' => ['list_string', 'blah'],
      'integer' => ['list_integer', 10],
    ];
  }

}
