<?php

/**
 * @file
 * Contains \Drupal\Tests\system_stream_wrapper\Kernel\File\ExtensionStreamTest.
 */

namespace Drupal\Tests\system_stream_wrapper\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests library discovery functions.
 *
 * @group system_stream_wrapper
 */
class LibraryDiscoveryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'system_stream_wrapper'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests loading a library that does not exist.
   */
  public function testLibraryLoad() {
    $this->assertFalse(file_exists('library://foo'));
  }

}
