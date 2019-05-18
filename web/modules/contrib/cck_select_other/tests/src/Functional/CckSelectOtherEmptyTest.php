<?php

namespace Drupal\Tests\cck_select_other\Functional;

/**
 * Tests that an empty value is not saved.
 *
 * @group cck_select_other
 */
class CckSelectOtherEmptyTest extends CckSelectOtherTestBase {

  /**
   * Asserts field validation for required attribute.
   */
  public function testEmpty() {
    $options = $this->createOptions();
    $storage_values = [
      'settings' => ['allowed_values' => $options],
      'cardinality' => 1,
    ];
    $config_values = ['required' => 0];
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
      ->responseNotContains('<div class="field__item"></div>');

    $edit = [
      $field_name . '[0][select_other_list]' => '_none',
    ];
    $this->drupalPostForm('/node/1/edit', $edit, 'Save');
    $this->assertSession()
      ->responseNotContains('<div class="field__item"></div>');
  }

}
