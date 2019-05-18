<?php

namespace Drupal\Tests\file_encrypt\Functional;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Entity\Key;
use Drupal\Tests\BrowserTestBase;

/**
 * Base test class for all kind of file encrypt tests.
 */
abstract class FunctionalTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['file_encrypt', 'encrypt', 'key', 'encrypt_test', 'node', 'file', 'field_ui', 'image'];

  /**
   * A list of testkeys.
   *
   * @var \Drupal\key\Entity\Key[]
   */
  protected $testKeys;

  /**
   * A list of test encryption profiles.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile[]
   */
  protected $encryptionProfiles;

  /**
   * @var boolean
   */
  protected $generatedTestFiles;

  /**
   * Creates test keys for usage in tests.
   */
  protected function createTestKeys() {
    // Create a 128bit testkey.
    $key_128 = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key_128->save();
    $this->testKeys['testing_key_128'] = $key_128;

    // Create a 256bit testkey.
    $key_256 = Key::create([
      'id' => 'testing_key_256',
      'label' => 'Testing Key 256 bit',
      'key_type' => "encryption",
      'key_type_settings' => ['key_size' => '256'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbitmustbesixteenbit'],
    ]);
    $key_256->save();
    $this->testKeys['testing_key_256'] = $key_256;
  }

  /**
   * Creates test encryption profiles for usage in tests.
   */
  protected function createTestEncryptionProfiles() {
    // Create test encryption profiles.
    $encryption_profile_1 = EncryptionProfile::create([
      'id' => 'encryption_profile_1',
      'label' => 'Encryption profile 1',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKeys['testing_key_128']->id(),
    ]);
    $encryption_profile_1->save();
    $this->encryptionProfiles['encryption_profile_1'] = $encryption_profile_1;

    $encryption_profile_2 = EncryptionProfile::create([
      'id' => 'encryption_profile_2',
      'label' => 'Encryption profile 2',
      'encryption_method' => 'config_test_encryption_method',
      'encryption_method_configuration' => ['mode' => 'CFB'],
      'encryption_key' => $this->testKeys['testing_key_256']->id(),
    ]);
    $encryption_profile_2->save();
    $this->encryptionProfiles['encryption_profile_2'] = $encryption_profile_2;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createTestKeys();
    $this->createTestEncryptionProfiles();

    $this->writeSettings([
      'settings' => [
        'encrypted_file_path' => (object) [
            'value' => $this->publicFilesDirectory,
            'required' => TRUE,
        ],
      ],
    ]);
  }

  /**
   * Gets a list of files that can be used in tests.
   *
   * The first time this method is called, it will call
   * simpletest_generate_file() to generate binary and ASCII text files in the
   * public:// directory. It will also copy all files in
   * core/modules/simpletest/files to public://. These contain image, SQL, PHP,
   * JavaScript, and HTML files.
   *
   * All filenames are prefixed with their type and have appropriate extensions:
   * - text-*.txt
   * - binary-*.txt
   * - html-*.html and html-*.txt
   * - image-*.png, image-*.jpg, and image-*.gif
   * - javascript-*.txt and javascript-*.script
   * - php-*.txt and php-*.php
   * - sql-*.txt and sql-*.sql
   *
   * Any subsequent calls will not generate any new files, or copy the files
   * over again. However, if a test class adds a new file to public:// that
   * is prefixed with one of the above types, it will get returned as well, even
   * on subsequent calls.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript',
   *   'php', 'sql', 'text'.
   * @param $size
   *   (optional) File size in bytes to match. Defaults to NULL, which will not
   *   filter the returned list by size.
   *
   * @return \Drupal\file\Entity\File[]
   *   List of files in public:// that match the filter(s).
   */
  protected function drupalGetTestFiles($type, $size = NULL) {
    if (empty($this->generatedTestFiles)) {
      // Generate binary test files.
      $lines = array(64, 1024);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('binary-' . $count++, 64, $line, 'binary');
      }

      // Generate ASCII text test files.
      $lines = array(16, 256, 1024, 2048, 20480);
      $count = 0;
      foreach ($lines as $line) {
        simpletest_generate_file('text-' . $count++, 64, $line, 'text');
      }

      // Copy other test files from simpletest.
      $original = drupal_get_path('module', 'simpletest') . '/files';
      $files = file_scan_directory($original, '/(html|image|javascript|php|sql)-.*/');
      foreach ($files as $file) {
        file_unmanaged_copy($file->uri, PublicStream::basePath());
      }

      $this->generatedTestFiles = TRUE;
    }

    $files = array();
    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
      $files = file_scan_directory('public://', '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->uri);
          if ($stats['size'] != $size) {
            unset($files[$file->uri]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  protected function drupalCompareFiles($file1, $file2) {
    $compare_size = filesize($file1->uri) - filesize($file2->uri);
    if ($compare_size) {
      // Sort by file size.
      return $compare_size;
    }
    else {
      // The files were the same size, so sort alphabetically.
      return strnatcmp($file1->name, $file2->name);
    }
  }

}
