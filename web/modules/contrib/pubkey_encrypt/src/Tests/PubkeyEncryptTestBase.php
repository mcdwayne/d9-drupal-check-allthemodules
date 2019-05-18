<?php

namespace Drupal\pubkey_encrypt\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for Pubkey Encrypt tests.
 */
abstract class PubkeyEncryptTestBase extends WebTestBase {

  public static $modules = array(
    'key',
    'encrypt',
    'encrypt_seclib',
    'pubkey_encrypt',
    'pubkey_encrypt_openssl',
    'pubkey_encrypt_password',
  );

  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Do not strict check all configuration saved till issue#2777983 in Encrypt
    // (https://www.drupal.org/node/2777983) gets fixed.
    $this->strictConfigSchema = FALSE;

    parent::setUp();

    // Have the module initialized.
    $this->initializePubkeyEncrypt();
  }

  /**
   * Initialize the module manually with default plugins.
   */
  protected function initializePubkeyEncrypt() {
    $config = \Drupal::service('config.factory')
      ->getEditable('pubkey_encrypt.initialization_settings');
    $config->set('module_initialized', 1)
      ->set('asymmetric_keys_generator', 'openssl_default')
      ->set('asymmetric_keys_generator_configuration', array('key_size' => '2048'))
      ->set('login_credentials_provider', 'user_passwords')
      ->save();
    // During testing, we cannot call the module initialization function
    // directly because it uses the Batch API. Hence doing the vital steps
    // manually.
    \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager')
      ->refreshReferenceVariables();
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    foreach ($users as $user) {
      \Drupal::service('pubkey_encrypt.pubkey_encrypt_manager')
        ->initializeUserKeys($user);
    }
  }

  /**
   * Custom login function to ensure the handling of cookies after a user login.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User object representing the user to log in.
   */
  protected function drupalLogin(AccountInterface $account) {
    parent::drupalLogin($account);

    // When a user logs in, his Private key gets temporarily stored in a cookie
    // and should be present there till he logs out. Since SimpleTest by default
    // does not provide the functionality of retaining cookies during curl
    // requests, hence manually doing it here as it is necessary.
    foreach ($this->cookies as $name => $value) {
      // We're only concerned with the cookie containing Private key for a user.
      if (preg_match('/.private_key/', $name, $matches)) {
        $_COOKIE[$name] = urldecode($value['value']);
      }
    }
  }

}
