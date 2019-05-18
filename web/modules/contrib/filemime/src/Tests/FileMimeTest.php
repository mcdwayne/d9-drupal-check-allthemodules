<?php

namespace Drupal\filemime\Tests;

use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;

/**
 * File MIME tests.
 *
 * @group File MIME
 */
class FileMimeTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filemime', 'file'];

  /**
   * Overrides WebTestBase::setUp().
   */
  protected function setUp() {
    parent::setUp();
    $this->web_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->web_user);
    $this->drupalGet('admin/config/media/filemime');
    $fields = ['types' => 'example/x-does-not-exist filemime'];
    $this->drupalPostForm(NULL, $fields, t('Save configuration'));
  }

  /**
   * Tests that a file MIME is set on the file object.
   */
  public function testFileMime() {
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.filemime',
      'uri' => 'public://druplicon.filemime',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');
    $file->save();
    $this->assertEqual($file->getMimeType(), 'example/x-does-not-exist', 'File MIME was set correctly.');
  }

}
