<?php

namespace Drupal\Tests\file_url\Kernel;

use Drupal\Component\Utility\Unicode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the file_url field item.
 *
 * @group file_url
 */
class FileUrlFieldItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file', 'file_url'];

  /**
   * Tests that the 'handler' field setting stores the proper plugin ID.
   *
   * @see \Drupal\Tests\field\Kernel\EntityReference\EntityReferenceItemTest::testSelectionHandlerSettings()
   */
  public function testSelectionHandlerSettings() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'file_url',
      'settings' => [
        'target_type' => 'file',
      ],
    ]);
    $field_storage->save();

    // Do not specify any value for the 'handler' setting in order to verify
    // that the default handler with the correct derivative is used.
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ]);
    $field->save();
    $field = FieldConfig::load($field->id());

    $this->assertEquals($field->getSetting('handler'), 'file_url_default:file');
  }

}
