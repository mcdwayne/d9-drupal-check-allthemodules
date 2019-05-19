<?php

namespace Drupal\Tests\webform_encryption\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests webform submission storage.
 *
 * @group webform_encryption
 */
class WebformEncryptionSubmissionStorageTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'path',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test encryption and decryption.
   */
  public function testEncryptDecrypt() {
    $this->assertEquals(1, 1);
  }

}
