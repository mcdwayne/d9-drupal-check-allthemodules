<?php

/**
 * @file
 * Contains Drupal\link_class_widget\Tests\LinClassWidgetUITest.
 */

namespace Drupal\link_class_widget\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests link class widget field formatter UI.
 *
 * @group link_class_widget
 */
class LinkClassWidgetUITest extends WebTestBase {

  private $type = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'link', 'field_ui', 'link_class_widget');

  protected function setUp() {
    parent::setUp();

    // Add a content type.
    $this->type = $this->drupalCreateContentType();

    // Create User
    $this->web_user = $this->drupalCreateUser(array('create ' . $this->type->type . ' content', 'edit own ' . $this->type->type . ' content', 'access content', 'administer nodes', 'administer content types', 'administer node fields', 'administer node form display'));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests that link class widget field UI functionality.
   */
  function testFieldUI() {

    /*$type_path = 'admin/structure/types/manage/' . $this->type->type;

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = Unicode::strtolower($label);
    $edit = array(
      'fields[_add_new_field][label]' => $label,
      'fields[_add_new_field][field_name]' => $field_name,
      'fields[_add_new_field][type]' => 'link',
    );
    $this->drupalPostForm("$type_path/fields", $edit, t('Save'));
    // Proceed to the Edit (field settings) page.
    $this->drupalPostForm(NULL, array(), t('Save field settings'));
    // Proceed to the Manage fields overview page.
    $this->drupalPostForm(NULL, array(), t('Save settings'));

    // Check, that there is a link_class_widget option for link form widget select.
    $this->drupalGet("$type_path/form-display");
    $link_class_op = $this->cssSelect('option[value="link_class"]');
    $this->assertEqual(count($link_class_op), 1);*/

    // Select the link_class_widget formatter
    /*$edit = array(
      'fields[field_' . $field_name . '][type]' => 'link_class',
    );
    $this->drupalPostForm("$type_path/form-display", $edit, t('Save'));
    $this->assertText(t('Your settings have been saved.'));

    // And check if the formatter setting got saved.
    $link_class_op = $this->cssSelect('option[value="link_class"][selected="selected"]');
    $this->assertEqual(count($link_class_op), 1);


    // Check if there is no class select on node edit, since we have not set any classes.
    $this->drupalGet('node/add/' . $this->type->type);
    $class_select = $this->cssSelect('select[name$="[_attributes][class]"]');
    $this->assertEqual(count($class_select), 0);

    // Check if we can save class settings
    $form_display = entity_load('entity_form_display', 'node.' . $this->type->type . '.default');
    $field = $form_display->getComponent('field_' . $field_name);
    $field['settings']['allowed_classes'] = 'default|Default
other|Other';

    $form_display->removeComponent('field_' . $field_name);
    $form_display->setComponent('field_' . $field_name, $field);
    $form_display->save();

    $form_display = entity_load('entity_form_display', 'node.' . $this->type->type . '.default');
    $field1 = $form_display->getComponent('field_' . $field_name);

    $this->assertEqual($field['settings']['allowed_classes'], $field1['settings']['allowed_classes']);

    $this->drupalGet("$type_path/form-display");

    // Check if there is a class select on node edit
    $this->drupalGet('node/add/' . $this->type->type);
    $class_select = $this->cssSelect('select[name$="[options][attributes][class]"]');
    $this->assertEqual(count($class_select), 1);

    // Check if the class select have the right children.
    $c = 0;
    foreach($class_select[0]->children() as $child) {

      $this->assertTrue(((string)$child['value'] == 'default' || 'other'));

      if((string)$child['value'] == 'default') {
        $this->assertEqual((string)$child[0], 'Default');
      }

      else if((string)$child['value'] == 'other') {
        $this->assertEqual((string)$child[0], 'Other');
      }

      $c++;
    }

    $this->assertEqual($c, 2);

    // Check: create node with link class
    $node = array(
      'title[0][value]' => 'Node title',
      'field_' . $field_name . '[0][url]' => 'http://example.com',
      'field_' . $field_name . '[0][title]' => 'Link title',
      'field_' . $field_name . '[0][options][attributes][class]' => 'other',
    );
    $this->drupalPostForm('node/add/' . $this->type->type, $node, t('Save and publish'));
    $this->assertResponse(200);

    // Check if the class was populated to the HTML element
    $link_selector = $this->cssSelect('a.other');
    $this->assertEqual(count($link_selector), 1);*/

    // Check, that node cannot be saved, when there was an illegal class option.
    /*$node = array(
      'title[0][value]' => 'Node title',
      'field_' . $field_name . '[0][url]' => 'http://example.com',
      'field_' . $field_name . '[0][title]' => 'Link title',
      'field_' . $field_name . '[0][options][attributes][class]' => 'lalelu',
    );

    $msg = '';

    try {
      $this->drupalPostForm(
        'node/add/' . $this->type->type,
        $node,
        t('Save and publish')
      );
    } catch(\Exception $e) {
      $msg = $e->getMessage();
    }

    $this->assertEqual($msg, 'Failed to set field to set field_' . $field_name . '[0][options][attributes][class] to ' . $node['field_' . $field_name . '[0][options][attributes][class]']);*/

  }

}
