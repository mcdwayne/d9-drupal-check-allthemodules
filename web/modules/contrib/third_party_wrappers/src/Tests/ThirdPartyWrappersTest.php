<?php

namespace Drupal\third_party_wrappers\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the third-party-wrappers page.
 *
 * @group third_party_wrappers
 */
class ThirdPartyWrappersTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['third_party_wrappers'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->config('third_party_wrappers.settings')->set('split_on', '<!-- third_party_wrappers -->');
    $this->config('third_party_wrappers.settings')->set('css_js_dir', 'third_party_wrappers');
    $this->config('third_party_wrappers.settings')->set('expire_age', 1);
    $this->config('third_party_wrappers.settings')->save();
  }

  /**
   * Tests ThirdPartyWrappers::cleanDirectory().
   */
  public function testCleanDirectory() {
    // Define the directory and file name.
    $directory = file_default_scheme() . '://third_party_wrappers_test';
    $file_path = $directory . '/test_file';
    // Prepare the directory.
    $prepared = file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $this->assertEqual(TRUE, $prepared, 'Directory was prepared.');
    // Create the test file.
    touch($file_path);
    $this->assertEqual(TRUE, file_exists($file_path), 'File exists.');
    // Wait long enough to ensure that the file is expired.
    sleep(2);
    // Try cleaning the directory and make sure the file is removed.
    \Drupal::service('third_party_wrappers')->cleanDirectory($directory, 1);
    $this->assertEqual(FALSE, file_exists($file_path), 'File no longer exists.');
  }

  /**
   * Tests ThirdPartyWrappers::copyFiles().
   */
  public function testCopyFiles() {
    // Define the directory and file names.
    $scheme = file_default_scheme();
    // The name of the file to be copied.
    $file_name = 'css_file.css';
    // The name of the directory that the file will be stored in.
    $file_directory_name = 'css';
    // The path to the directory that the file will be stored in.
    $file_directory = $scheme . '://' . $file_directory_name;
    // The path to the file to be copied.
    $file_path = $file_directory . '/' . $file_name;
    // The directory where copied files should be stored.
    $third_party_wrappers_dir = $this->config('third_party_wrappers.settings')->get('css_js_dir');
    // The path that the file will be copied to.
    $copied_file_path = $scheme . '://' . $third_party_wrappers_dir . '/' . $file_directory_name . '/' . $file_name;
    // Prepare the directory.
    $prepared = file_prepare_directory($file_directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    $this->assertEqual(TRUE, $prepared, 'Source directory was prepared.');
    // Create the test file.
    touch($file_path);
    $this->assertEqual(TRUE, file_exists($file_path), 'Source file exists.');
    // Copy the file.
    /** @var \Drupal\Core\StreamWrapper\LocalStream $wrapper */
    // Use the wrapper to convert a URI to something like "sites/default/files".
    $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
    // Build the path. This is passed in in place of the full HTML structure of
    // a page, since ThirdPartyWrappers::copyFiles() only scans for file paths
    // anyway.
    $check_path = $wrapper->getDirectoryPath() . '/' . $file_directory_name . '/' . $file_name;
    // Prevent false positives by making sure the file does not exist yet.
    $this->assertEqual(FALSE, file_exists($copied_file_path), 'Copied file does not exist yet.');
    // Copy the file.
    \Drupal::service('third_party_wrappers')->copyFiles($check_path, 'css');
    // Test that the file now exists.
    $this->assertEqual(TRUE, file_exists($copied_file_path), 'Copied file exists.');
  }

  /**
   * Tests ThirdPartyWrappers::getMaxAge().
   */
  public function testGetMaxAge() {
    // Test that getMaxAge won't return NULL.
    $this->config('third_party_wrappers.settings')->set('expire_age', NULL);
    $this->config('third_party_wrappers.settings')->save();
    $max_age = \Drupal::service('third_party_wrappers')->getMaxAge();
    $this->assertEqual(0, $max_age, 'Max age defaults to 0.');
    // Test the getMaxAge returns the properly set value.
    $this->config('third_party_wrappers.settings')->set('expire_age', 1);
    $this->config('third_party_wrappers.settings')->save();
    $max_age = \Drupal::service('third_party_wrappers')->getMaxAge();
    $this->assertEqual(1, $max_age, 'Max age returns actual value.');
  }

  /**
   * Tests ThirdPartyWrappers::getSplitOn().
   */
  public function testGetSplitOn() {
    // Test that getSplitOn won't return NULL.
    $this->config('third_party_wrappers.settings')->set('split_on', NULL);
    $this->config('third_party_wrappers.settings')->save();
    $split_on = \Drupal::service('third_party_wrappers')->getSplitOn();
    $this->assertEqual('', $split_on, 'Split on defaults to an empty string.');
    // Test the getSplitOn returns the properly set value.
    $this->config('third_party_wrappers.settings')->set('split_on', '<!-- third_party_wrappers -->');
    $this->config('third_party_wrappers.settings')->save();
    $split_on = \Drupal::service('third_party_wrappers')->getSplitOn();
    $this->assertEqual('<!-- third_party_wrappers -->', $split_on, 'Split on returns actual value.');
  }

  /**
   * Tests ThirdPartyWrappers::getDir().
   */
  public function testGetDir() {
    // Test that getDir won't return NULL.
    $this->config('third_party_wrappers.settings')->set('css_js_dir', NULL);
    $this->config('third_party_wrappers.settings')->save();
    $dir = \Drupal::service('third_party_wrappers')->getDir();
    $this->assertEqual('', $dir, 'Get dir defaults to an empty string.');
    // Test the getDir returns the properly set value.
    $this->config('third_party_wrappers.settings')->set('css_js_dir', 'third_party_wrappers');
    $this->config('third_party_wrappers.settings')->save();
    $dir = \Drupal::service('third_party_wrappers')->getDir();
    $this->assertEqual('third_party_wrappers', $dir, 'Get dir returns actual value.');
  }

  /**
   * Tests ThirdPartyWrappers::getUri().
   */
  public function testGetUri() {
    $scheme = file_default_scheme();
    // Test that getUri won't return NULL.
    $this->config('third_party_wrappers.settings')->set('css_js_dir', NULL);
    $this->config('third_party_wrappers.settings')->save();
    $uri = \Drupal::service('third_party_wrappers')->getUri();
    $this->assertEqual('', $uri, 'Get URI defaults to an empty string.');
    // Test the getUri returns the properly set value.
    $this->config('third_party_wrappers.settings')->set('css_js_dir', 'third_party_wrappers');
    $this->config('third_party_wrappers.settings')->save();
    $uri = \Drupal::service('third_party_wrappers')->getUri();
    $this->assertEqual($scheme . '://third_party_wrappers', $uri, 'Get URI returns actual value.');
  }

}
