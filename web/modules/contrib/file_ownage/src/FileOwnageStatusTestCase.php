<?php

namespace Drupal\file_ownage;

/**
 * Tests status checks on existing file entities.
 */
class FileOwnageStatusTestCase extends FileOwnageTestHelper {

  /**
   * GetInfo.
   *
   * @inheritdoc
   */
  public static function getInfo() {
    return [
      'name' => 'File ownage status checks',
      'description' => 'Create an unstable file index and reviews its status.',
      'group' => 'File ownage',
    ];
  }

  /**
   * Setup.
   *
   * @inheritdoc
   */
  public function setUp() {
    $this->profile = 'minimal';
    parent::setUp();
  }

  /**
   * Check file Status functionality.
   */
  public function testFileStatus() {
    // Create file (via API, not UI).
    $test_file = $this->getTestFile('text');
    // That has the file URI, but createFileEntity takes filepath.
    $scheme = \Drupal::service("file_system")->uriScheme($test_file->uri);
    $filepath = file_uri_target($test_file->uri);
    $settings = [
      'scheme' => $scheme,
      'filepath' => $filepath,
      'filemime' => 'text/plain',
    ];
    $file_entity = $this->createFileEntity($settings);

    // Check that the file now exists in the database.
    $file = $this->getFileByFilename($test_file->filename);
    $this->assertTrue($file, t('File found in database.'), 'File ownage');

    // Verify the file status using file_ownage.
    $check = file_ownage_check_file_action($file_entity, []);
    // This is expected to confirm the file status.
    $this->assertTrue($check == FILE_STATUS_PERMANENT, 'File status is found and correct after insertion.');

    // New delete the file badly, creating an unstable state.
    file_unmanaged_delete($file_entity->uri);

    // Re-check the file status using file_ownage.
    $check = file_ownage_check_file_action($file_entity, []);
    // This is expected to confirm the file is missing.
    $this->assertTrue($check == FILE_OWNAGE_IS_INVALID, 'File status is correctly detected as missing after deletion.');

  }

}
