<?php

/**
 * @file
 * Contains Drupal\encrypt_seclib\Tests\PHPSecLibEncryptTest.
 */

namespace Drupal\encrypt_seclib\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the PHPSecLib encryption method.
 *
 * @group encrypt_seclib
 */
class PHPSecLibEncryptTest extends WebTestBase {

  /**
   * Exempt from strict schema checking.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  // @TODO: remove if https://www.drupal.org/node/2666196 is fixed.
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('key', 'encrypt', 'encrypt_seclib');

  /**
   * An administrator user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
    ]);
  }

  /**
   * Test adding an encryption profile and encrypting / decrypting with it.
   */
  public function testEncryptAndDecrypt() {
    $this->drupalLogin($this->adminUser);

    // Create a test Key entity.
    $this->drupalGet('admin/config/system/keys/add');
    $edit = [
      'key_type' => 'aes_encryption',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'key_type');
    $edit = [
      'key_provider' => 'config',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'key_provider');

    $edit = [
      'id' => 'testing_key',
      'label' => 'Testing Key',
      'key_type' => "aes_encryption",
      'key_type_settings[key_size]' => '128',
      'key_provider' => 'config',
      'key_input_settings[key_value]' => 'mustbesixteenbit',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    $saved_key = \Drupal::service('key.repository')->getKey('testing_key');
    $this->assertTrue($saved_key, 'Key was succesfully saved.');

    // Create an encryption profile config entity.
    $this->drupalGet('admin/config/system/encryption/profiles/add');

    // Check if the plugin exists.
    $this->assertOption('edit-encryption-method', 'phpseclib', t('Encryption method option is present.'));
    $this->assertText('PHP Secure Communications Library (phpseclib)', t('Encryption method text is present'));

    $edit = [
      'encryption_method' => 'phpseclib',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'encryption_method');

    $edit = [
      'id' => 'test_encryption_profile',
      'label' => 'Test encryption profile',
      'encryption_method' => 'phpseclib',
      'encryption_key' => 'testing_key',
    ];
    $this->drupalPostForm('admin/config/system/encryption/profiles/add', $edit, t('Save'));

    $encryption_profile = \Drupal::service('entity.manager')->getStorage('encryption_profile')->load('test_encryption_profile');
    $this->assertTrue($encryption_profile, 'Encryption profile was succesfully saved.');

    // Test the encryption service with our encryption profile.
    $test_string = 'testing 123 &*#';
    $enc_string = \Drupal::service('encryption')->encrypt($test_string, $encryption_profile);
    $this->assertEqual(base64_encode($enc_string), 'mXe3e038G8PL7aDCb42u4g==', 'The encryption service is properly processing');

    // Test the decryption service with our encryption profile.
    $dec_string = \Drupal::service('encryption')->decrypt($enc_string, $encryption_profile);
    $this->assertEqual($dec_string, $test_string, 'The decryption service is properly processing');
  }

}
