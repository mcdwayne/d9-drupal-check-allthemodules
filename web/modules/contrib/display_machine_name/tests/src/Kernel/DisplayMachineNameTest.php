<?php

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the display_machine_name module.
 *
 * @group display_machine_name
 * @coversDefaultClass \Drupal\display_machine_name\Service\DisplayMachineNameService
 */
class DisplayMachineNameTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_ui',
    'field',
    'node',
    'text',
    'user',
    'system',
    'display_machine_name',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installConfig('field');

    // Create a 'page' node type.
    $type = NodeType::create(['type' => 'page', 'name' => 'page']);
    $type->save();

    // Add the body field for testing with.
    node_add_body_field($type);
  }

  /**
   * Tests that the machine name shows when applicable using the body field.
   */
  public function testDisplayMachineName() {
    // Build the node page default entity_form_display form.
    $form_display = EntityFormDisplay::load('node.page.default');
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('entity_form_display', 'edit')
      ->setEntity($form_display);
    $form = $this->container->get('form_builder')->getForm($form_object);
    $clean_form_label = $form['fields']['body']['human_name']['#plain_text'];

    // Body field has default label.
    $this->assertEquals('Body', $clean_form_label);

    // Check that the enable display_machine_name checkbox is there.
    $this->assertTrue(isset($form[DISPLAY_MACHINE_NAME_ENABLED_ID]));

    $name_service = $this->container->get('display_machine_name.general_service');
    $name_service->enableDisplayMachineName($form_display);

    // Rebuild the form.
    $rebuilt_form = $this->container->get('form_builder')->getForm($form_object);
    $rebuilt_form_label = $rebuilt_form['fields']['body']['human_name']['#plain_text'];

    // Check that label has been changed.
    $this->assertEquals($name_service->getChangedFieldLabel('Body', 'body'), $rebuilt_form_label);
  }

}
