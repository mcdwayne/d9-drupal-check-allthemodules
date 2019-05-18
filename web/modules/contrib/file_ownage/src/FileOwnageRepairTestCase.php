<?php

namespace Drupal\file_ownage;

/**
 * Tests status checks on existing file entities.
 */
class FileOwnageRepairTestCase extends FileOwnageTestHelper {
  protected $webuser;
  protected $adminuser;
  protected $group;

  /**
   * GetInfo.
   *
   * @inheritdoc
   */
  public static function getInfo() {
    return [
      'name' => 'File ownage repair checks',
      'description' => 'Create an unstable file index and tries to repair it.',
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
    $this->group = 'File ownage';
    parent::setUp();

    return;

    $this->webuser = $this->drupalCreateUser([
      'edit own document files',
      'create files',
    ]);
    $this->adminuser = $this->drupalCreateUser([
      'bypass file access',
      'administer files',
    ]);
  }

  /**
   * Check file Repair functionality.
   */
  public function testFileRepair() {
    // $this->drupalLogin($this->webuser);.
    $scheme = file_default_scheme();

    // Create files (via API, not UI).
    // Create them in a subfolder in the files dir.
    $folder_uri = $scheme . '://testfiles/';
    $dir_prepared = file_prepare_directory($folder_uri, FILE_CREATE_DIRECTORY);
    $this->assertTrue($dir_prepared, 'testfiles dir is ready for content', $this->group);
    $starter_files = [
      'control',
      'delete_me',
      'rename_me',
      'move_me_to_subdir',
      'move_me_to_parent',
      'find_me_remotely',
    ];
    $working_entities = [];
    foreach ($starter_files as $starter_id) {
      $test_file = $this->getTestFile('text');
      $destination_uri = $folder_uri . $starter_id . '.txt';
      file_unmanaged_move($test_file->uri, $destination_uri);
      $filepath = file_uri_target($destination_uri);
      $settings = [
        'scheme' => $scheme,
        'filepath' => $filepath,
        'filemime' => 'text/plain',
      ];
      $working_entities[$starter_id] = $this->createFileEntity($settings);
    }

    // Made a bunch of samples. Get them into scope for convenience;.
    extract($working_entities);

    // Now start destroying them.
    // Leave 'control' alone. Mess up the others.
    $success = file_unmanaged_delete($delete_me->uri);
    $this->assertTrue($success, 'Deleted test file', $this->group);

    $success = file_unmanaged_move($rename_me->uri, $folder_uri . 'renamed' . '.txt');
    $this->assertTrue($success, 'Renamed test file', $this->group);

    $subdir = $folder_uri . 'subdir/';
    file_prepare_directory($subdir, FILE_CREATE_DIRECTORY);
    $success = file_unmanaged_move($move_me_to_subdir->uri, $subdir . $move_me_to_subdir->filename);
    $this->assertTrue($success, 'Moved test file down', $this->group);

    $success = file_unmanaged_move($move_me_to_parent->uri, $scheme . '://' . $move_me_to_parent->filename);
    $this->assertTrue($success, 'Moved test file up', $this->group);

    $success = file_unmanaged_delete($find_me_remotely->uri);
    $this->assertTrue($success, 'Removed remote-only file', $this->group);

    // Verify all of them. Most will be lost now.
    foreach ($working_entities as $file_entity) {
      file_ownage_check_file_action($file_entity, []);
    }

    $this->assertTrue($control->status == FILE_STATUS_PERMANENT, 'Control File status is correct. (still there)', $this->group);
    $this->assertTrue($delete_me->status == FILE_OWNAGE_IS_INVALID, 'Deleted File status is correct. (lost)', $this->group);
    $this->assertTrue($rename_me->status == FILE_OWNAGE_IS_INVALID, 'Renamed File status is correct. (lost)', $this->group);
    $this->assertTrue($move_me_to_subdir->status == FILE_OWNAGE_IS_INVALID, 'Demoted File status is correct. (lost)', $this->group);
    $this->assertTrue($move_me_to_parent->status == FILE_OWNAGE_IS_INVALID, 'Promoted File status is correct. (lost)', $this->group);
    $this->assertTrue($find_me_remotely->status == FILE_OWNAGE_IS_INVALID, 'Remote File status is correct. (lost)', $this->group);

    // Repair all of them. See what happens.
    $repair_instructions = file_ownage_default_settings();
    // Add the main file dir and the subfolder as seek paths so we
    // can find the moved files.
    $repair_instructions['seek_paths'][] = $subdir;
    $repair_instructions['seek_paths'][] = 'public://';
    // Getting a little self-referential, I can make a remote lookup
    // and find the lost file in my own repo.
    $repair_instructions['seek_paths'][] = 'http://cgit.drupalcode.org/file_ownage/plain/tests/';

    foreach ($working_entities as $file_entity) {
      $repaired = file_ownage_seek_file_action($file_entity, $repair_instructions);
    }

    // Re-check them and see what has been found.
    $this->assertTrue($control->status == FILE_STATUS_PERMANENT, 'Control file status is correct. (unchanged)', $this->group);
    // Not expecting to have found this.
    $this->assertTrue($delete_me->status == FILE_OWNAGE_IS_INVALID, 'Deleted file status is correct. (stil lost)', $this->group);
    // Not expecting to have found this.
    $this->assertTrue($rename_me->status == FILE_OWNAGE_IS_INVALID, 'Renamed file status is correct. (stil lost)', $this->group);
    // This one should be found under the alternate path again.
    $this->assertTrue($move_me_to_subdir->status == FILE_STATUS_PERMANENT, 'Demoted file status is correct. (rediscovered)', $this->group);
    // This one should be found under the alternate path again.
    $this->assertTrue($move_me_to_parent->status == FILE_STATUS_PERMANENT, 'Promoted file status is correct. (rediscovered)', $this->group);
    // This one should have been retrieved from the web.
    $this->assertTrue($find_me_remotely->status == FILE_STATUS_PERMANENT, 'Remotely retrieved file status is correct. (rediscovered)', $this->group);

  }

}
