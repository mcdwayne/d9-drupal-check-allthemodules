<?php

namespace Drupal\Tests\uaparser\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests ua-parser functionality.
 *
 * @group ua-parser
 */
class UaparserTest extends BrowserTestBase {

  protected $uaParserAdmin = 'admin/config/system/uaparser';
  protected $parser;

  public static $modules = ['uaparser'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->parser = $this->container->get('uaparser');
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
    ]));
  }

  /**
   * Tests ua-parser functionality.
   */
  public function testUaParser() {

    $ua = "Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.110 Safari/537.36";

    // Check both client and parser time are returned.
    $res = $this->parser->parse($ua);
    $this->assertTrue(isset($res['client']));
    $this->assertTrue(isset($res['time']));

    // After caching, check only client info is returned.
    $res = $this->parser->parse($ua);
    $this->assertTrue(isset($res['client']));
    $this->assertFalse(isset($res['time']));

    // Remove regexes.php forcing an update, both client and parser time are
    // returned.
    file_unmanaged_delete_recursive(\Drupal::config('uaparser.settings')->get('regexes_file_location'));
    $ua = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36";
    $res = $this->parser->parse($ua);
    $this->assertTrue(isset($res['client']));
    $this->assertTrue(isset($res['time']));
  }

  /**
   * Test settings form.
   */
  public function testFormAndSettings() {
    $regexes_dir = \Drupal::config('uaparser.settings')->get('regexes_file_location');

    // The default regexes directory has been created by install.
    $this->assertTrue(is_dir($regexes_dir));
    $files_count = count(file_scan_directory($regexes_dir, '/.*/'));
    $this->assertEqual(0, $files_count);

    // Loading the form, the regexes.php file is created.
    $this->drupalGet($this->uaParserAdmin);
    $this->assertTrue(is_dir($regexes_dir));
    $files_count = count(file_scan_directory($regexes_dir, '/.*/'));
    $this->assertEqual(1, $files_count);

    // Change the regexes directory.
    $new_regexes_dir = 'public://test_uaparser';
    file_prepare_directory($new_regexes_dir, FILE_CREATE_DIRECTORY);
    $edit = [
      'regexes_file_location' => $new_regexes_dir,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');

    // The new regexes directory is created, and config is updated
    // accordingly.
    $this->assertEqual($new_regexes_dir, \Drupal::config('uaparser.settings')->get('regexes_file_location'));
    $this->assertTrue(is_dir($new_regexes_dir));
    $files_count = count(file_scan_directory($new_regexes_dir, '/.*/'));
    $this->assertEqual(1, $files_count);
  }

}
