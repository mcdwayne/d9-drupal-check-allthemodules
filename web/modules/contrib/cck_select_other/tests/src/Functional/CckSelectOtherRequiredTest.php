<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests that required fields work correctly.
 *
 * @group cck_select_other
 */
class CckSelectOtherRequiredTest extends CckSelectOtherTestBase {

  /**
   * Asserts required field validation.
   */
  public function testRequired() {
    $options = $this->createOptions();
    $storage_values = [
      'settings' => ['allowed_values' => $options],
      'cardinality' => 1,
    ];
    $config_values = ['required' => 1];
    $field = $this->createSelectOtherListField('list_string', $storage_values, $config_values);
    $field_name = $field->getName();

    // Log in and try to create content with an empty value.
    $this->drupalLogin($this->webUser);

    $edit = [
      'title[0][value]' => $this->randomString(25),
      $field_name . '[0][select_other_list]' => 'other',
      $field_name . '[0][select_other_text_input]' => '',
    ];
    $this->drupalPostForm('/node/add/' . $this->contentType->id(), $edit, 'Save');
    $this->assertSession()
      ->responseContains('You must provide a value for this option.');
    $this->assertSession()
      ->elementExists('xpath', '//input[@name="' . $field_name . '[0][select_other_text_input]" and contains(@class, "error")]');

    // Successfully post the form.
    $value = $this->getRandomGenerator()->word(25);
    $edit[$field_name . '[0][select_other_text_input]'] = $value;
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()
      ->pageTextContains($value);
  }

}
