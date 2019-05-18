<?php

namespace Drupal\drd\Crypt\Method;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Crypt\BaseMethod;

/**
 * Provides OpenSSL encryption functionality.
 *
 * @ingroup drd
 */
class Openssl extends BaseMethod {

  private $cipher;

  private $iv;

  private $password;

  private $supportedCipher = array(
    'aes-256-ctr' => 32,
    'aes-128-cbc' => 16,
  );

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings = array()) {
    $this->cipher = isset($settings['cipher']) ? $settings['cipher'] : $this->getDefaultCipher();
    $this->password = !empty($settings['password']) ? $settings['password'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'OpenSSL';
  }

  /**
   * {@inheritdoc}
   */
  public function getCipher() {
    return $this->cipher;
  }

  /**
   * {@inheritdoc}
   */
  public function getPassword() {
    return base64_decode($this->password);
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return function_exists('openssl_encrypt');
  }

  /**
   * Get the default cipher which is the first from the list of available ones.
   */
  private function getDefaultCipher() {
    $ciphers = $this->getCipherMethods();
    return empty($ciphers) ? '' : array_shift($ciphers);
  }

  /**
   * Calculate key length for the selected cipher.
   */
  private function getKeyLength() {
    return isset($this->supportedCipher[$this->cipher]) ? $this->supportedCipher[$this->cipher] : 32;
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods() {
    $result = array();
    $available = openssl_get_cipher_methods();
    foreach ($this->supportedCipher as $cipher => $keyLength) {
      if (in_array($cipher, $available)) {
        $result[$cipher] = $cipher;
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array $condition) {
    $form['openssl_cipher'] = array(
      '#type' => 'select',
      '#title' => t('Cipher'),
      '#options' => $this->getCipherMethods(),
      '#default_value' => $this->cipher,
      '#states' => array(
        'required' => $condition,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValues(FormStateInterface $form_state) {
    $cipher = $form_state->getValue('openssl_cipher');
    $reset = (empty($this->password) ||
      $cipher != $this->cipher);
    $this->cipher = $cipher;
    if ($reset) {
      $this->resetPassword();
    }

    return $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function resetPassword() {
    $this->password = $this->generatePassword($this->getKeyLength());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    /* @var \Drupal\drd\Encryption $service */
    $service = \Drupal::service('drd.encrypt');
    $settings = array(
      'cipher' => $this->cipher,
      'password' => $this->password,
    );
    $service->encrypt($settings);
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getIv() {
    if (empty($this->iv)) {
      $nonceSize = openssl_cipher_iv_length($this->cipher);
      $this->iv = openssl_random_pseudo_bytes($nonceSize);
    }
    return $this->iv;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args) {
    return empty($this->password) ?
      '' :
      openssl_encrypt(
        serialize($args),
        $this->cipher,
        $this->getPassword(),
        OPENSSL_RAW_DATA,
        $this->getIv()
      );
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    $this->iv = $iv;
    return unserialize(openssl_decrypt(
      $body,
      $this->cipher,
      $this->getPassword(),
      OPENSSL_RAW_DATA,
      $this->iv
    ));
  }

}

if (!defined('OPENSSL_RAW_DATA')) {
  define('OPENSSL_RAW_DATA', 1);
}
