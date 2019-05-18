<?php

namespace Drupal\Tests\real_estate_property\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\real_estate_property\Entity\PropertyType;

/**
 * Ensure the property type works correctly.
 *
 * @group real_estate
 */
class PropertyTypeTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'block',
    'field',
    'field_ui',
    'image',
    'text',
    'options',
    'real_estate_property',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'administer real_estate_property display',
      'administer real_estate_property fields',
      'administer real_estate_property form display',
      'administer real estate property type',
    ]);
    $this->drupalLogin($user);

  }

  /**
   * Tests whether the default property type was created.
   */
  public function testDefaultPropertyType() {

    $property_type = PropertyType::load('default');
    $this->assertNotEmpty(!empty($property_type), 'The default property type is available.');

    $this->drupalGet('admin/real-estate/config/property-types');
    $rows = $this->getSession()->getPage()->find('css', 'table tbody tr');
    $this->assertEquals(count($rows), 1, '1 property type is correctly listed.');
  }

  /**
   * Tests creating a property type using a form.
   */
  public function testPropertyTypeCreation() {

    $this->drupalGet('admin/structure/property-type/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, t('Save'));
    $property_type = PropertyType::load($edit['id']);
    $this->assertNotEmpty(!empty($property_type), 'The new property type has been created.');
    $this->assertEquals($property_type->label(), $edit['label'], 'The new property type has the correct label.');

    // Check if 'agencies' field is added.
    $this->drupalGet('admin/structure/property-type/' . $property_type->id() . '/edit/fields');
    $el = $this->getSession()->getPage()->find('css', 'table tbody tr td:first-child');
    $this->assertEquals($el->getText(), 'Agency', 'A Agency field is added.');

    // Check if for a field 'agencies' set a widget "Autocomplete".
    $this->drupalGet('admin/structure/property-type/' . $property_type->id() . '/edit/form-display');
    $el = $this->getSession()->getPage()->find('css', 'table tbody tr:nth-child(2) td:nth-child(5) select option[selected="selected"]');
    $this->assertEquals($el->getText(), 'Autocomplete', 'For a field "agencies" set a widget "Autocomplete"');
  }

  /**
   * Tests editing a property type using a form.
   */
  public function testPropertyTypeEditing() {

    $this->drupalGet('admin/structure/property-type/default/edit');
    $edit = [
      'label' => 'Default2',
    ];
    $this->submitForm($edit, t('Save'));
    $property_type = PropertyType::load('default');
    $this->assertEquals($property_type->label(), $edit['label'], 'The label of the property type has been changed.');
  }

  /**
   * Tests deleting a property type via a form.
   */
  public function testPropertyTypeDeletion() {
    // Create a property type.
    $this->drupalGet('admin/structure/property-type/add');
    $edit = [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(),
    ];
    $this->submitForm($edit, t('Save'));
    $property_type = PropertyType::load($edit['id']);
    $this->assertNotEmpty(!empty($property_type), 'The new property type has been created.');
    $this->assertEquals($property_type->label(), $edit['label'], 'The new property type has the correct label.');

    // Delete a property type.
    $this->drupalGet('admin/structure/property-type/' . $property_type->id() . '/delete');
    $this->assertSession()->pageTextContains(t('This action cannot be undone.'));
    $this->submitForm([], 'Delete');
    $exists = (bool) PropertyType::load($property_type->id());
    $this->assertEmpty($exists, 'The new property type has been deleted from the database.');
  }

}
