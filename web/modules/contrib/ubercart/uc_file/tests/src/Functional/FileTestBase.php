<?php

namespace Drupal\Tests\uc_file\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Base class for file download feature tests.
 */
abstract class FileTestBase extends UbercartBrowserTestBase {

  public static $modules = ['uc_payment', 'uc_payment_pack', 'uc_file'];

  protected $testFilename = '';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need admin permissions in order to change file download settings.
    $this->drupalLogin($this->adminUser);

    // Set up directory for files to live in.
    $this->configureDownloadDirectory();

    // Load one test file so we always have something to download.
    // Use the Ubercart README.txt because we know it will always be there
    // and we know in advance how big it is.
    $filename = drupal_get_path('module', 'uc_file') . '/../README.txt';
    $this->setTestFile($filename);
  }

  /**
   * Helper function to get test file name.
   *
   * @return string
   *   The base name of the test file.
   */
  public function getTestFile() {
    return $this->testFilename;
  }

  /**
   * Helper function to upload test file for downloading.
   *
   * @param string $filename
   *   The fully-qualified name of the file to upload.
   */
  public function setTestFile($filename) {
    // First delete existing file, if set.
    if (!empty($this->testFilename)) {
      \Drupal::service('file_system')->unlink($this->tempFilesDirectory . '/' . $this->testFilename);
    }

    // Copy new file to downloads directory.
    copy(
      $filename,
      $this->tempFilesDirectory . '/' . basename($filename)
    );
    $this->testFilename = basename($filename);
  }

  /**
   * Helper function to configure the file downloads directory.
   *
   * Uses BrowserTestBase::tempFilesDirectory as a place to store the downloads
   * for the tests, but this is NOT where you'd put the downloads directory on
   * a live site. On a live site, it should be outside the web root.
   */
  protected function configureDownloadDirectory() {
    $this->drupalPostForm(
      'admin/store/config/file',
      ['base_dir' => $this->tempFilesDirectory],
      'Save configuration'
    );

    $this->assertFieldByName(
      'base_dir',
      $this->tempFilesDirectory,
      'Download file path has been set.'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Cleanup - delete our test file.
    \Drupal::service('file_system')->unlink($this->tempFilesDirectory . '/' . $this->testFilename);
    parent::tearDown();
  }

}
