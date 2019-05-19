<?php

namespace Drupal\sodium\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\Random;

/**
 * Tests encryption and decryption with the Sodium encryption method.
 *
 * @group sodium
 */
class SodiumEncryptionTest extends WebTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('key', 'encrypt', 'sodium');

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * A test key.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $testKey;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
    ]);
    $this->drupalLogin($this->adminUser);
    $this->createTestKey();
  }

  /**
   * Creates a test key.
   */
  protected function createTestKey() {
    // Create a 256-bit key.
    $this->drupalGet('admin/config/system/keys/add');
    $edit = [
      'key_type' => 'encryption',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'key_type');
    $edit = [
      'key_provider' => 'config',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'key_provider');

    $edit = [
      'id' => 'sodium_test_key',
      'label' => 'Sodium Test Key',
      'key_type' => "encryption",
      'key_type_settings[key_size]' => '256',
      'key_provider' => 'config',
      'key_input_settings[key_value]' => '12345678901234567890123456789012',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->testKey = \Drupal::service('key.repository')->getKey('sodium_test_key');
    $this->assertTrue($this->testKey, 'Sodium test key was successfully saved.');
  }

  /**
   * Test encryption and decryption with the Sodium encryption profile.
   */
  public function testEncryptDecrypt() {
    // Create an encryption profile.
    $this->drupalGet('admin/config/system/encryption/profiles/add');

    // Check if the plugin exists.
    $this->assertOption('edit-encryption-method', 'sodium', 'Sodium is available as an encryption method.');
    $this->assertText('Sodium', 'Sodium encryption method text is present.');

    $edit = [
      'encryption_method' => 'sodium',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'encryption_method');

    $edit = [
      'id' => 'sodium_encryption_profile',
      'label' => 'Sodium encryption profile',
      'encryption_method' => 'sodium',
      'encryption_key' => $this->testKey->id(),
    ];

    // Save the encryption profile.
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Confirm that the encryption profile was successfully saved.
    $encryption_profile = \Drupal::service('entity.manager')->getStorage('encryption_profile')->load('sodium_encryption_profile');
    $this->assertTrue($encryption_profile, 'Sodium encryption profile was successfully saved.');

    // Create random text to use for testing.
    $random = new Random();
    $test_plaintext = $random->string(20);

    // Encrypt the test text and confirm it's different from the plaintext.
    $encrypted_text = \Drupal::service('encryption')->encrypt($test_plaintext, $encryption_profile);
    $this->assertNotEqual($test_plaintext, $encrypted_text, 'The test text was successfully encrypted');

    // Decrypt the encrypted text and confirm it's the same as the plaintext.
    $decrypted_text = \Drupal::service('encryption')->decrypt($encrypted_text, $encryption_profile);
    $this->assertEqual($test_plaintext, $decrypted_text, 'The test text was successfully decrypted');
  }

}
