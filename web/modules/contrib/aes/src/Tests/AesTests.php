<?php
namespace Drupal\aes\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\aes\AES;

/**
 * Test encryption/decryption
 *
 * @group aes
 */
class AesTests extends WebTestBase {

  /**
   * Modules to enable. Enable the aes module.
   *
   * @var array
   */
  public static $modules = array('aes');

  protected $strictConfigSchema = FALSE;

  /**
   * String that will be encrypted.
   *
   * @var string
   */
  protected $string = 'String-to-encrypt';

  /**
   * {@inheritdoc}
   * Aes module needs to set the active directory in the setting.php file in
   * order to be installed. Set it here.
   */
  protected function writeSettings(array $settings) {
    $settings['config_directories'][CONFIG_ACTIVE_DIRECTORY] = (object) array(
      'value' => $this->privateFilesDirectory . '/aes',
      'required' => TRUE,
    );
    parent::writeSettings($settings);
  }

  /**
   * Encrypt a string and decrypt it back.
   */
  protected function testAesEncryption() {

    $encrypted = AES::encrypt($this->string);
    $decrypted = AES::decrypt($encrypted);
    $this->assertNotEqual($encrypted, $this->string, 'String has been encrypted');
    $this->assertEqual($decrypted, $this->string, 'String has been successfully decrypted');
  }
}
