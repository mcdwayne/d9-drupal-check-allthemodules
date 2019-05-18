<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Tests the field storage configuration UI.
 *
 * @group file_encrypt
 */
class FieldStorageConfigurationTest extends FunctionalTestBase {

  /**
   * Tests the field storage adding.
   */
  public function testFieldStorageSettingsForm() {
    $account = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
    ]);
    $this->drupalLogin($account);

    NodeType::create([
      'type' => 'page',
    ])->save();

    $assert = $this->assertSession();
    // Test a file field.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $assert->statusCodeEquals(200);

    $this->submitForm([
      'new_storage_type' => 'file',
      'field_name' => 'test_file',
      'label' => 'New file field',
    ], 'Save and continue');

    $this->submitForm([
      'settings[uri_scheme]' => 'encrypt',
      'settings[encryption_profile]' => 'encryption_profile_1',
    ], 'Save field settings');

    $field_storage_config = FieldStorageConfig::load('node.field_test_file');
    $this->assertEquals('encryption_profile_1', $field_storage_config->getThirdPartySetting('file_encrypt', 'encryption_profile'));

    $field_config = FieldConfig::load('node.page.field_test_file');
    $this->assertStringStartsWith('encryption_profile_1', $field_config->getSetting('file_directory'));

    // Test an image field.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $assert->statusCodeEquals(200);

    $this->submitForm([
      'new_storage_type' => 'image',
      'field_name' => 'test_image',
      'label' => 'New image field',
    ], 'Save and continue');

    $this->submitForm([
      'settings[uri_scheme]' => 'encrypt',
      'settings[encryption_profile]' => 'encryption_profile_1',
    ], 'Save field settings');

    $field_storage_config = FieldStorageConfig::load('node.field_test_image');
    $this->assertEquals('encryption_profile_1', $field_storage_config->getThirdPartySetting('file_encrypt', 'encryption_profile'));

    $field_config = FieldConfig::load('node.page.field_test_file');
    $this->assertStringStartsWith('encryption_profile_1', $field_config->getSetting('file_directory'));
  }

}
