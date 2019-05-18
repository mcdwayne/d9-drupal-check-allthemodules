<?php

namespace Drupal\Tests\range\Functional\Formatter;

/**
 * Tests the unformatted field formatter UI.
 *
 * @group range
 */
class RangeUnformattedFormatterUiTest extends RangeFormatterUiTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->fieldType = 'range_float';

    parent::setUp();
  }

  /**
   * Tests Unformatted formatter.
   */
  public function testUnformattedFormatter() {
    $this->drupalGet('entity_test/structure/entity_test/display');

    // Enable Unformatted formatter.
    $edit = [
      "fields[$this->fieldName][parent]" => 'content',
      "fields[$this->fieldName][region]" => 'content',
      "fields[$this->fieldName][type]" => 'range_unformatted',
    ];
    $this->submitForm($edit, 'Save');

    // Ensure that summary is correct.
    $this->assertSession()->pageTextContains('1234.123456789-4321.0987654321');
    $this->assertSession()->pageTextNotContains('Equivalent values will be combined into a single value.');
    $this->assertSession()->pageTextNotContains('Display with FROM value prefix and suffix.');
    $this->assertSession()->pageTextNotContains('Display with TO value prefix and suffix.');

    // Go to formatter settings form.
    $this->submitForm([], $this->fieldName . '_settings_edit');

    // Ensure that form displays correct set of fields.
    $this->assertSession()->fieldValueEquals('Range separator', '-');
    $this->assertSession()->fieldNotExists('Thousand marker');
    $this->assertSession()->fieldNotExists('Decimal marker');
    $this->assertSession()->fieldNotExists('Scale');
    $this->assertSession()->fieldNotExists('Combine equivalent values');
    $this->assertSession()->fieldNotExists('Display FROM value prefix and suffix');
    $this->assertSession()->fieldNotExists('Display TO value prefix and suffix');

    // Update formatter settings.
    $edit = [
      "fields[$this->fieldName][settings_edit_form][settings][range_separator]" => '=',
    ];
    $this->submitForm($edit, 'Save');

    // Ensure that summary is correct.
    $this->assertSession()->pageTextContains('1234.123456789=4321.0987654321');

    // Go to formatter settings form.
    $this->submitForm([], $this->fieldName . '_settings_edit');

    // Ensure that form displays correct set of fields.
    $this->assertSession()->fieldValueEquals('Range separator', '=');
  }

}
