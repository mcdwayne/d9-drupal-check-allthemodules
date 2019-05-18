<?php

/**
 * @file
 * Contains Drupal\entityblock\Tests\EntityBlockFieldUITest.
 */

namespace Drupal\entityblock\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests entityblock field UI functionality.
 *
 * @group entityblock
 */
class EntityBlockFieldUITest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'entityblock', 'field_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser(['administer content types', 'administer node fields', 'administer node display', 'administer node form display']));
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests that entityblock field UI functionality does not generate warnings.
   */
  function testFieldUI() {
    $view_modes = \Drupal::service('entity_display.repository')->getViewModes('node');

    // Add a content type.
    $type1 = $this->drupalCreateContentType();
    $content_type1 = $type1->id();
    $bundle_path1 = 'admin/structure/types/manage/' . $type1->id();

    // Add a entityblock field to the newly-created type.
    $field_label = $this->randomMachineName();
    $field_name = Unicode::strtolower($field_label);
    $this->fieldUIAddNewField($bundle_path1, $field_name, $field_label, 'entityblock');

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    $this->drupalGet("$bundle_path1/form-display");
    $this->assertRaw(t('View modes: %view_modes', array('%view_modes' => $view_modes['full']['label'])));

    // Add another content type.
    $type2 = $this->drupalCreateContentType();
    $content_type2 = $type2->id();
    $bundle_path2 = 'admin/structure/types/manage/' . $content_type2;

    // Add an existing field to the second node type.
    $this->fieldUIAddExistingField($bundle_path2, 'field_' . $field_name, $field_label);

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    $this->drupalGet("$bundle_path2/form-display");
    $this->assertRaw(t('View modes: %view_modes', array('%view_modes' => $view_modes['full']['label'])));

    // Test the summaries of the form display settings.
    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('node.' . $content_type2 . '.default')
      ->setComponent('field_' . $field_name, array(
        'type' => 'entityblock_default',
        'settings' => array(
          'view_modes' => array('teaser' => 'teaser'),
          'force_enabled' => TRUE,
          'force_title' => TRUE,
        ),
      ))
      ->save();

    $this->drupalGet("$bundle_path2/form-display");
    $this->assertRaw(t('View modes: %view_modes', array('%view_modes' => $view_modes['teaser']['label'])));
    $this->assertText(t('Force "Display as block"'));
    $this->assertText(t('Force "Block title"'));

    // Delete fields.
    $this->fieldUIDeleteField($bundle_path1, 'node.' . $content_type1 . '.field_' . $field_name, $field_label, $type1->label());
    $this->fieldUIDeleteField($bundle_path2, 'node.' . $content_type2 . '.field_' . $field_name, $field_label, $type2->label());

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $type1->id(), $field_name), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('node', $field_name), 'Field storage was deleted.');

    // Check that the field was deleted.
    $this->assertNull(FieldConfig::loadByName('node', $type2->id(), $field_name), 'Field was deleted.');
    // Check that the field storage was deleted too.
    $this->assertNull(FieldStorageConfig::loadByName('node', $field_name), 'Field storage was deleted.');
  }

}
