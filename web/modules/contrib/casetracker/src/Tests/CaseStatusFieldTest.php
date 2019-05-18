<?php
/**
 * @file
 * Tests for field types.
 */

namespace Drupal\casetracker\Tests;

use Drupal\casetracker;
use Drupal\casetracker\CaseTrackerWebTestBase;

/**
 * Class CaseStatusFieldTest
 * @group casetracker
 * @ingroup casetracker
 */
class CaseStatusFieldTest extends CaseTrackerWebTestBase
{
  /**
   * Test basic functionality of the csae status  field.
   *
   * - Creates a content type.
   * - Adds a single-valued field_example_rgb to it.
   * - Adds a multivalued field_example_rgb to it.
   * - Creates a node of the new type.
   * - Populates the single-valued field.
   * - Populates the multivalued field with two items.
   * - Tests the result.
   */
  public function testSingleValueField() {
  // Add a single field as administrator user.
  $this->drupalLogin($this->administratorAccount);
  $this->fieldName = $this->createField('casetracker_state', 'casetracker_state_widget', '1');

  // Now that we have a content type with the desired field, switch to the
  // author user to create content with it.
  $this->drupalLogin($this->authorAccount);
  $this->drupalGet('node/add/' . $this->contentTypeName);

  // Add a node.
  $title = $this->randomMachineName(20);
  $edit = array(
    'title[0][value]' => $title,
    //@TODO: Set correct values for field width
    // 'field_' . $this->fieldName . '[0][value]' => '#000001',
  );

  // Create the content.
  $this->drupalPostForm(NULL, $edit, t('Save'));
  $this->assertText(t('@type @title has been created', array('@type' => $this->contentTypeName, '@title' => $title)));

  // Verify the value is shown when viewing this node.
  //@TODO: Replace with proper field for text widget
  $output_strings = $this->xpath("//div[contains(@class,'field-type-casetracker')]/div/div/p/text()");
  $this->assertEqual((string) $output_strings[0], "The color code in this field is #000001");
}

}