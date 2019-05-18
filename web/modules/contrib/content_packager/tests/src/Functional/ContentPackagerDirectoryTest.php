<?php

namespace Drupal\Tests\content_packager\Functional;

use Drupal\content_packager\BatchOperations;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;

/**
 * Provides methods specifically for testing Content Packager's configuration.
 *
 * @group content_packager
 */
class ContentPackagerDirectoryTest extends BrowserTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }
  use ImageFieldCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'content_packager'];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'access administration pages',
      'administer site configuration',
      'administer content packager',
      'create content package',
    ]);

    $this->drupalLogin($this->user);
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *   A test File object with filesize added.
   */
  public function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));

    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);

    return File::create((array) $file);
  }

  /**
   * Tests that destination config is working.
   */
  public function testDestinationConfig() {
    $this->drupalGet('admin/config/content/content_packager');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [
      'package_scheme' => 'public://',
      'package_destination' => 'test_folder',
    ], t('Save configuration'));

    $package_uri = content_packager_package_uri();
    $this->assertFileExists($package_uri, 'Directory exists.');
    $this->assertFileExists($package_uri . DIRECTORY_SEPARATOR . '.htaccess', 'Directory .htaccess exists.');

    $file = $this->getTestFile('text');

    $batch_context = [
      'results' => ['completed_count' => 0, 'failed' => []],
      'finished' => NULL,
      'sandbox' => [],
    ];

    $zip_name = 'test.zip';
    $file_uri = $file->getFileUri();

    // Pathinfo doesn't like scheme uris.  Or least uris where there isn't a
    // folder, eg. "public://file.txt".
    $file_path = \Drupal::service('file_system')->realpath($file_uri);

    $file_pathinfo = pathinfo($file_path);
    $zipfile_dir = \Drupal::service('file_system')->realpath($package_uri);

    BatchOperations::zipFile($zip_name, $zipfile_dir, $file_pathinfo['basename'], $file_pathinfo['dirname'], $batch_context);

    /* @var  \Drupal\Core\File\FileSystem  $file_manager */
    $zipfile_path = $zipfile_dir . DIRECTORY_SEPARATOR . $zip_name;
    $this->assertFileExists($zipfile_path, 'File copy exists where expected.');
  }

  /**
   * Ensure that the CreatePackage form correctly detects existing packages.
   */
  public function testPackageCreateFormDetection() {

    // Make sure everything is configured!
    $this->drupalGet('admin/config/content/content_packager');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [
      'package_scheme' => 'public://',
      'package_destination' => 'test_folder',
    ], t('Save configuration'));

    $file = $this->getTestFile('text');
    $file_uri = $file->getFileUri();
    $batch_context = [
      'results' => ['completed_count' => 0, 'failed' => []],
      'finished' => NULL,
      'sandbox' => [],
    ];

    $package_uri = content_packager_package_uri();
    $package_path = \Drupal::service('file_system')->realpath($package_uri);
    $batch_context['sandbox'] = [];

    $zip_name = \Drupal::config('content_packager.settings')->get('zip_name');
    $file_pathinfo = pathinfo(\Drupal::service('file_system')->realpath($file_uri));

    BatchOperations::zipFile($zip_name, $package_path, $file_pathinfo['basename'], $file_pathinfo['dirname'], $batch_context);

    $this->assertFileExists($package_uri . DIRECTORY_SEPARATOR . $zip_name, 'Zip file exists where expected.');

    $this->drupalGet('admin/content/package');
    $this->assertSession()->responseContains('A package already exists', 'Form indicates package already exists.');
  }

}
