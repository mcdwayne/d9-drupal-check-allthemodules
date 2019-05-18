<?php

/**
 * @file
 * Contains Drupal\entityreference\Tests\entityReferenceAdminTest.
 */

namespace Drupal\entityreference\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test for Entity Reference admin UI.
 */
class entityReferenceAdminTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Entity Reference UI',
      'description' => 'Tests for the administrative UI.',
      'group' => 'Entity Reference',
    );
  }

  public static $modules = array('field_ui', 'entityreference');

  public function setUp() {
    parent::setUp();

    // Create test user.
    $this->admin_user = $this->drupalCreateUser(array('access content', 'administer content types'));
    $this->drupalLogin($this->admin_user);

    // Create content type, with underscores.
    $type_name = strtolower($this->randomName(8)) . '_test';
    $type = $this->drupalCreateContentType(array('name' => $type_name, 'type' => $type_name));
    $this->type = $type->type;
  }

  protected function assertFieldSelectOptions($name, $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', array(':name' => $name));
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $this->getAllOptionsList($field);
      return $this->assertIdentical($options, $expected_options);
    }
    else {
      return $this->fail(t('Unable to find field @name', array('@name' => $name)));
    }
  }

  /**
   * Extract all the options of a select element.
   */
  protected function getAllOptionsList($element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = (string) $option['value'];
    }
    // TODO: support optgroup.
    return $options;
  }

  public function testFieldAdminHandler() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    // First step: 'Add new field' on the 'Manage fields' page.
    $this->drupalPost($bundle_path . '/fields', array(
      'fields[_add_new_field][label]' => 'Test label',
      'fields[_add_new_field][field_name]' => 'test',
      'fields[_add_new_field][type]' => 'entityreference',
      'fields[_add_new_field][widget_type]' => 'entityreference_autocomplete',
    ), t('Save'));

    // Node should be selected by default.
    $this->assertFieldByName('field[settings][target_type]', 'node');
    // The base handler should be selected by default.
    $this->assertFieldByName('field[settings][handler]', 'base');

    // The base handler settings should be diplayed.
    $entity_type = 'node';
    $entity_info = entity_get_info($entity_type);
    foreach ($entity_info['bundles'] as $bundle_name => $bundle_info) {
      $this->assertFieldByName('field[settings][handler_settings][target_bundles][' . $bundle_name . ']');
    }

    // Test the sort settings.
    $options = array('none', 'property', 'field');
    $this->assertFieldSelectOptions('field[settings][handler_settings][sort][type]', $options);
    // Option 0: no sort.
    $this->assertFieldByName('field[settings][handler_settings][sort][type]', 'none');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][property]');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][field]');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][direction]');
    // Option 1: sort by property.
    $this->drupalPostAJAX(NULL, array('field[settings][handler_settings][sort][type]' => 'property'), 'field[settings][handler_settings][sort][type]');
    $this->assertFieldByName('field[settings][handler_settings][sort][property]', '');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][field]');
    $this->assertFieldByName('field[settings][handler_settings][sort][direction]', 'ASC');
    // Option 2: sort by field.
    $this->drupalPostAJAX(NULL, array('field[settings][handler_settings][sort][type]' => 'field'), 'field[settings][handler_settings][sort][type]');
    $this->assertNoFieldByName('field[settings][handler_settings][sort][property]');
    $this->assertFieldByName('field[settings][handler_settings][sort][field]', '');
    $this->assertFieldByName('field[settings][handler_settings][sort][direction]', 'ASC');
    // Set back to no sort.
    $this->drupalPostAJAX(NULL, array('field[settings][handler_settings][sort][type]' => 'none'), 'field[settings][handler_settings][sort][type]');

    // Second step: 'Instance settings' form.
    $this->drupalPost(NULL, array(), t('Save field settings'));

    // Third step: confirm.
    $this->drupalPost(NULL, array(), t('Save settings'));

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//td[1]', 'Test label', t('Field was created and appears in the overview page.'));
  }
}
