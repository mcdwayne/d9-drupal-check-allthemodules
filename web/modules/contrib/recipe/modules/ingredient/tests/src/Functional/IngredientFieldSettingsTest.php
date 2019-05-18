<?php

namespace Drupal\Tests\ingredient\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the functionality of the ingredient field settings.
 *
 * @group recipe
 */
class IngredientFieldSettingsTest extends BrowserTestBase {

  use IngredientTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'ingredient', 'node'];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create a new content type for testing.
    $this->ingredientCreateContentType();

    // Create and log in the admin user.
    $permissions = [
      'create test_bundle content',
      'access content',
      'administer node fields',
      'administer node display',
      'add ingredient',
      'view ingredient',
      'administer site configuration',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests ingredient field settings.
   */
  public function testIngredientFieldSettings() {
    // Create an ingredient field on the test_bundle node type.
    $field_settings = [
      'unit_sets' => [
        'us',
        'si',
        'common',
      ],
      'default_unit' => '',
    ];
    $this->createIngredientField([], $field_settings);

    // Visit the field settings page and verify that the settings are selected.
    $this->drupalGet('admin/structure/types/manage/test_bundle/fields/node.test_bundle.field_ingredient');
    $this->assertSession()->checkboxChecked('edit-settings-unit-sets-us');
    $this->assertSession()->checkboxChecked('edit-settings-unit-sets-si');
    $this->assertSession()->checkboxChecked('edit-settings-unit-sets-common');
    $option_field = $this->assertSession()->optionExists('edit-settings-default-unit', '');
    $this->assertTrue($option_field->hasAttribute('selected'), 'The blank default unit was selected.');

    // Visit the node edit page and verify that we can find units from each of
    // the enabled sets and that the select element shows the empty option by
    // default.
    $this->drupalGet('node/add/test_bundle');
    $this->assertEquals($this->xpath("//option[@value='cup']")[0]->getText(), 'cup (c)', 'Found an option from the U.S. customary unit set.');
    $this->assertEquals($this->xpath("//option[@value='milliliter']")[0]->getText(), 'milliliter (ml)', 'Found an option from the SI/Metric unit set.');
    $this->assertEquals($this->xpath("//option[@value='tablespoon']")[0]->getText(), 'tablespoon (T)', 'Found an option from the Common unit set.');
    $option_field = $this->assertSession()->optionExists('edit-field-ingredient-0-unit-key', '');
    $this->assertTrue($option_field->hasAttribute('selected'), 'The empty unit option was selected.');

    // Update the field settings and disable the SI/Metric unit set.  Then
    // verify that its unit cannot be found on the node edit page.  Also verify
    // that the default unit is selected.
    $field_settings = [
      'unit_sets' => [
        'us',
        'common',
      ],
      'default_unit' => 'cup',
    ];
    $this->updateIngredientField($field_settings);
    $this->drupalGet('node/add/test_bundle');
    $this->assertEquals($this->xpath("//option[@value='cup']")[0]->getText(), 'cup (c)', 'Found an option from the U.S. customary unit set.');
    $this->assertEmpty($this->xpath("//option[@value='milliliter']"), 'Did not find an option from the SI/Metric unit set.');
    $this->assertEquals($this->xpath("//option[@value='tablespoon']")[0]->getText(), 'tablespoon (T)', 'Found an option from the Common unit set.');
    $option_field = $this->assertSession()->optionExists('edit-field-ingredient-0-unit-key', 'cup');
    $this->assertTrue($option_field->hasAttribute('selected'), 'The default unit was selected.');

    // Update the field settings and disable all unit sets to verify that all
    // units will then appear in the edit form by default.
    $field_settings = [
      'unit_sets' => [],
      'default_unit' => '',
    ];
    $this->updateIngredientField($field_settings);
    $this->drupalGet('node/add/test_bundle');
    $this->assertEquals($this->xpath("//option[@value='cup']")[0]->getText(), 'cup (c)', 'Found an option from the U.S. customary unit set.');
    $this->assertEquals($this->xpath("//option[@value='milliliter']")[0]->getText(), 'milliliter (ml)', 'Found an option from the SI/Metric unit set.');
    $this->assertEquals($this->xpath("//option[@value='tablespoon']")[0]->getText(), 'tablespoon (T)', 'Found an option from the Common unit set.');
  }

}
