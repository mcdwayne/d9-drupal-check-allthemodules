<?php

/**
 * @file
 * Contains Drupal\textfield_confirm\Tests\TextfieldConfirmTest.
 */

namespace Drupal\textfield_confirm\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for the textfield_confirm form element.
 *
 * @group textfield_confirm
 */
class TextfieldConfirmTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['textfield_confirm_test'];

  /**
   * Tests that things don't get all splody.
   */
  public function test() {
    $this->drupalGet('form_1');
    $this->checkLabels();
    $this->checkRequiredValidation();

    // Test basic form submit.
    $this->assertPostValue('testfield', 'Hello world');

    // Test that '0' works as an input.
    $this->assertPostValue('testfield', '0');

    // Test that the fields have to have the same value.
    $edit = ['testfield[text1]' => 'value1', 'testfield[text2]' => 'value2'];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertUniqueText(t('The specified fields do not match.'));
    $this->assertHasClass('testfield[text1]', 'error');
    $this->assertHasClass('testfield[text2]', 'error');
  }

  /**
   * Checks that the titles and descriptions are showing up.
   */
  protected function checkLabels() {
    $this->assertUniqueText(t('The wrapper title'));
    $this->assertUniqueText(t('The wrapper description.'));
    $this->assertUniqueText(t('Field one'));
    $this->assertUniqueText(t('Field two'));
    $this->assertUniqueText(t('This is the first field.'));
    $this->assertUniqueText(t('This is the second field. The value must match the first field.'));
    $this->assertHasClass('testfield[text1]', 'textfield-confirm-field');
    $this->assertHasClass('testfield[text2]', 'textfield-confirm-confirm');
    $this->assertHasClass('testfield[text1]', 'required');
    $this->assertHasClass('testfield[text2]', 'required');
  }

  /**
   * Checks the required validation.
   */
  protected function checkRequiredValidation() {
    $this->drupalPostForm(NULL, [], t('Submit'));
    $this->assertUniqueText(t('The wrapper title field is required.'));
    $this->assertHasClass('testfield[text1]', 'error');
    $this->assertHasClass('testfield[text2]', 'error');
  }

  /**
   * Asserts that a form field has a given class.
   *
   * @param string $name
   *   The name of the form field.
   * @param string $class
   *   The class to check for.
   */
  protected function assertHasClass($name, $class) {
    $classes = explode(' ', (string) $this->getFieldAttribute($name, 'class'));
    $this->assertTrue(in_array($class, $classes), SafeMarkup::format('@field has class @class.', ['@field' => $name, '@class' => $class]));
  }

  /**
   * Returns the attribute value of an input element.
   *
   * @param string $name
   *   The name attribute of the form field.
   * @param string $attribute
   *   The attribute to retrieve.
   *
   * @return string|bool
   *   The attribute value, or false if it doesn't exist.
   */
  protected function getFieldAttribute($name, $attribute) {
    $xpath = $this->constructFieldXpath('name', $name);
    $field = $this->xpath($xpath);
    return isset($field[0][$attribute]) ? (string) $field[0][$attribute] : FALSE;
  }

  /**
   * Checks that a value was input and received by the submit handler.
   *
   * @var string $value
   *   The form input value.
   */
  protected function assertPostValue($field, $value) {
    $edit = [$field . '[text1]' => $value, $field . '[text2]' => $value];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertUniqueText(t('The input value was: @value.', ['@value' => $value]));
  }

}
