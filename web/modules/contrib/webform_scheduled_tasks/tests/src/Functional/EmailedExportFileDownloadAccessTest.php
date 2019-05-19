<?php

namespace Drupal\webform_scheduled_tasks\Functional;

use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\Task\EmailedExport;

/**
 * Test the generated submission file access.
 *
 * @group webform_scheduled_tasks
 */
class EmailedExportFileDownloadAccessTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'webform_scheduled_tasks',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $dir = EmailedExport::DESTINATION_DIRECTORY;
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
  }

  /**
   * Test access to file downloads.
   */
  public function testFileDownloadAccess() {
    // The file must be in the correct directory to get access controlled.
    $this->assertFileDownloadAccess('private://unrelated.zip', [], 403);
    $this->assertFileDownloadAccess('private://unrelated_1.zip', [
      'administer webform submission',
    ], 403);
    $this->assertFileDownloadAccess(EmailedExport::DESTINATION_DIRECTORY . '/foo.zip', [], 403);
    $this->assertFileDownloadAccess(EmailedExport::DESTINATION_DIRECTORY . '/foo_1.zip', [
      'administer webform submission',
    ], 200);
  }

  /**
   * Test access to downloading a file.
   */
  public function assertFileDownloadAccess($file_uri, $permissions, $access_result) {
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $file = File::create([
      'uri' => $file_uri,
    ]);
    file_put_contents($file->getFileUri(), 'data');
    $file->setPermanent();
    $file->save();

    $this->drupalGet(file_create_url($file_uri));
    $this->assertSession()->statusCodeEquals($access_result);
  }

}
