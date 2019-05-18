<?php

namespace Drupal\custom_add_another\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Tests\FileFieldTestBase;

/**
 * Test case for 'Remove' button label alter.
 *
 * @group custom_add_another
 */
class RemoveButtonTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'file',
    'file_module_test',
    'field_ui',
    'custom_add_another',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file system manager.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Tests changes of multiple fields buttons labels.
   */
  function testRemoveButtonLabelAlter() {
    $type_name = 'article';
    $field_name = 'test_file_field';
    $test_file = $this->getTestFile('text');

    // Creating field and checking labels.
    $this->createFileField($field_name, 'node', $type_name, ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED]);
    $this->drupalGet("node/add/$type_name");
    $edit = [
      'files[' . $field_name . '_0][]' => $this->fileSystem->realpath($test_file->getFileUri()),
    ];
    $this->drupalPostForm(NULL, $edit, t('Upload'));

    $button_name = $field_name . '_0_remove_button';
    $remove_button_xpath = '//input[@name="' . $button_name . '"]';
    $this->assertFieldsByValue($this->xpath('.' . $remove_button_xpath), t('Remove'), 'Found the "Remove" button with default value.');

    // Updating field settings and checking labels again.
    $updated_add_more_value = $this->randomString();
    $updated_remove_value = $this->randomString();
    $this
      ->entityTypeManager
      ->getStorage('field_config')
      ->load('node.' . $type_name . '.' . $field_name)
      ->setThirdPartySetting('custom_add_another', 'custom_add_another', $updated_add_more_value)
      ->setThirdPartySetting('custom_add_another', 'custom_remove', $updated_remove_value)
      ->save();

    $this->drupalGet("node/add/$type_name");
    $edit = ['files[' . $field_name . '_0][]' => $this->fileSystem->realpath($test_file->getFileUri())];
    $this->drupalPostForm(NULL, $edit, $updated_add_more_value);

    $this->assertFieldsByValue($this->xpath('.' . $remove_button_xpath), $updated_remove_value, 'Found the "Remove" button with updated value.');
  }

}
