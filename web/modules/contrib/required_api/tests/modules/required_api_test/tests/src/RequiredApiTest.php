<?php

/**
 * @file
 * Contains \Drupal\required_api_test\Tests\RequiredApiTest.
 */

namespace Drupal\required_api_test\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\Language;
use Drupal\required_api_test\Tests\RequiredApiTestBase;

/**
 * Tests the functionality of the 'Manage fields' screen.
 */
class RequiredApiTest extends RequiredApiTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Required API',
      'description' => 'Test the required api base behavior.',
      'group' => 'Required API',
    );
  }

  function setUp() {

    parent::setUp();

    // Create a test field and instance.
    $this->field_name = 'test';

    $this->container->get('entity_type.manager')
      ->getStorage('field_entity')
      ->create(array(
        'name' => $this->field_name,
        'entity_type' => 'node',
        'type' => 'test_field'
      ))
      ->save();

    $this->instance = $this->container->get('entity_type.manager')
      ->getStorage('field_instance')
      ->create(array(
        'field_name' => $this->field_name,
        'entity_type' => 'node',
        'bundle' => $this->type,
      ))
      ->save();

    $this->instance->save();

    $form_display = entity_get_form_display('node', $this->type, 'default');
    $form_display->setComponent($this->field_name)->save();

    $this->manager = $form_display->get('pluginManager')->getRequiredManager();

    $this->admin_path = 'admin/structure/types/manage/' . $this->type . '/fields/' . $this->instance->id();

  }

  /**
   * Tests that default value is correctly validated and saved.
   */
  public function testExpectedPluginDefinitions() {

    $expected_definitions = array(
      // Core behavior plugin replacement.
      'default',
      // Testing plugins.
      'required_true',
    );

    $diff = array_diff($this->manager->getDefinitionsIds(), $expected_definitions);
    $this->assertEqual(array(), $diff, 'Definitions match expected.');

  }

  /**
   * Tests the default Required Plugin.
   */
  public function testRequiredDefaultPlugin() {

    // Setting default (FALSE) and checking the form.
    $this->_setRequiredPlugin('default', FALSE);

    $add_path = 'node/add/' . $this->type;
    $this->drupalGet($add_path);
    $title = $this->randomString();

    $edit = array(
      'title[0][value]' => $title,
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $message = t('!label !title has been created.', array(
        '!label' => $this->type_label,
        '!title' => $title,
      )
    );

    $this->assertText($message);
  }

  /**
   * Tests that default value is correctly validated and saved.
   */
  public function testRequiredTestTruePlugin() {

    // Setting true and checking the form.
    $this->_setRequiredPlugin('required_true', 1);
    $this->drupalGet('node/add/' . $this->type);

    $edit = array(
      'title[0][value]' => $this->randomString(),
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText(t('!field field is required.', array('!field' => $this->field_name)));
  }

  /**
   * Helper function to set the required Plugin.
   * @param [type] $plugin_id    [description]
   * @param [type] $plugin_value [description]
   */
  public function _setRequiredPlugin($plugin_id, $plugin_value) {

    $fieldname = "required_api[third_party_settings][required_plugin]";

    $edit = array(
      $fieldname => $plugin_id,
      'instance[required]' => $plugin_value,
    );

    $this->drupalPostForm($this->admin_path, $edit, t('Save settings'));

  }
}
