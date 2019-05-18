<?php

namespace Drupal\Tests\gender\Functional;

use Drupal\Core\Url;

/**
 * Tests the modifications to the help text for the gender field.
 *
 * @group gender
 */
class HelpFieldTest extends GenderTestBase {

  /**
   * The CSS selector for the label element.
   *
   * @var string
   */
  const LABEL_ELEMENT_SELECTOR = 'label[for=edit-description]';

  /**
   * The CSS selector for the field description.
   *
   * @var string
   */
  const DESCRIPTION_ELEMENT_SELECTOR = '#edit-description--description';

  /**
   * The CSS selector for the field element.
   *
   * @var string
   */
  const HELP_FIELD_ELEMENT_SELECTOR = 'textarea[name=description]';

  /**
   * The additional help field text.
   *
   * @var string
   */
  const NEW_HELP_TEXT = 'This data should only be collected when needed, and you should provide justification to your users for why you are asking for it.';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_ui',
    'gender',
    'gender_test',
    'help',
    'node',
    'text',
    'user',
  ];

  /**
   * Tests that the gender field help text field is changed.
   */
  public function testGenderHelpField() {
    // Get the field configuration page.
    $this->getFieldConfigPage('field_gender');
    // Get the text from the label element.
    $label_text = $this->getTextFromElement(self::LABEL_ELEMENT_SELECTOR);
    // Assert that the text has changed.
    $this->assertEquals('Justification for asking for this information', $label_text);
    // Get the text from the description element.
    $description_text = $this->getTextFromElement(self::DESCRIPTION_ELEMENT_SELECTOR);
    // Assert that the description text has been modified by adding the new
    // text.
    $this->assertContains(self::NEW_HELP_TEXT, $description_text);
    // Find the help text field itself.
    $help_text_field = $this->assertSession()->elementExists('css', self::HELP_FIELD_ELEMENT_SELECTOR);
    // Assert that the field is required.
    $this->assertEquals('required', $help_text_field->getAttribute('required'));
  }

  /**
   * Tests that the text field help text field is not changed.
   */
  public function testTextHelpField() {
    // Get the field configuration page.
    $this->getFieldConfigPage('field_other');
    // Get the text from the label element.
    $label_text = $this->getTextFromElement(self::LABEL_ELEMENT_SELECTOR);
    // Assert that the text has not changed.
    $this->assertEquals('Help text', $label_text);
    // Get the text from the description element.
    $description_text = $this->getTextFromElement(self::DESCRIPTION_ELEMENT_SELECTOR);
    // Assert that the description text has not been modified.
    $this->assertNotContains(self::NEW_HELP_TEXT, $description_text);
    // Find the help text field itself.
    $help_text_field = $this->assertSession()->elementExists('css', self::HELP_FIELD_ELEMENT_SELECTOR);
    // Assert that the field is required.
    $this->assertEmpty($help_text_field->getAttribute('required'));
  }

  /**
   * Load the field configuration page for a field and return the page object.
   *
   * @param string $field_name
   *   The name of the field.
   */
  protected function getFieldConfigPage($field_name) {
    // Build the URL to the page.
    $url = Url::fromRoute('entity.field_config.node_field_edit_form', [
      'node_type' => 'gender_test',
      'field_config' => 'node.gender_test.' . $field_name,
    ]);
    // Load the page.
    $this->drupalGet($url);
  }

  /**
   * Get the text from an element.
   *
   * @param string $selector
   *   The CSS selector for the element.
   *
   * @return string
   *   The element's text.
   */
  protected function getTextFromElement($selector) {
    // Find the element.
    $element = $this->assertSession()->elementExists('css', $selector);
    // Get the text from the element.
    $text = $element->getText();

    return $text;
  }

}
