<?php

namespace Drupal\drd\Crypt\Method;

use Drupal\Core\Form\FormStateInterface;
use Drupal\drd\Crypt\BaseMethod;

/**
 * Provides MCrypt encryption functionality.
 *
 * @ingroup drd
 */
class Mcrypt extends BaseMethod {

  private $cipher;

  private $mode;

  private $iv;

  private $password;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $settings = array()) {
    $this->cipher = isset($settings['cipher']) ? $settings['cipher'] : 'rijndael-256';
    $this->mode = isset($settings['mode']) ? $settings['mode'] : 'cbc';
    $this->password = !empty($settings['password']) ? $settings['password'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'MCrypt';
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
    return function_exists('mcrypt_encrypt');
  }

  /**
   * {@inheritdoc}
   */
  public function getCipherMethods() {
    return array(
      'rijndael-128',
      'rijndael-192',
      'rijndael-256',
    );
  }

  /**
   * Get list of mcrpyt modes.
   */
  public function getModes() {
    return array(
      'ecb',
      'cbc',
      'cfb',
      'ofb',
      'nofb',
      'stream',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array &$form, array $condition) {
    $form['mcrypt_cipher'] = array(
      '#type' => 'select',
      '#title' => t('Cipher'),
      '#options' => $this->getCipherMethods(),
      '#default_value' => array_search($this->cipher, $this->getCipherMethods()),
      '#states' => array(
        'required' => $condition,
      ),
    );
    $form['mcrypt_mode'] = array(
      '#type' => 'select',
      '#title' => t('Mode'),
      '#options' => $this->getModes(),
      '#default_value' => array_search($this->mode, $this->getModes()),
      '#states' => array(
        'required' => $condition,
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormValues(FormStateInterface $form_state) {
    $ciphers = $this->getCipherMethods();
    $modes = $this->getModes();

    $cipher = $ciphers[$form_state->getValue('mcrypt_cipher')];
    $mode = $modes[$form_state->getValue('mcrypt_mode')];
    $reset = (empty($this->password) ||
      $cipher != $this->cipher ||
      $mode != $this->mode);
    $this->cipher = $cipher;
    $this->mode = $mode;
    if ($reset) {
      $this->resetPassword();
    }

    return $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function resetPassword() {
    /* @noinspection PhpDeprecationInspection */
    $this->password = $this->generatePassword(mcrypt_get_key_size($this->cipher, $this->mode));
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
      'mode' => $this->mode,
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
      /* @noinspection PhpDeprecationInspection */
      $nonceSize = mcrypt_get_iv_size($this->cipher, $this->mode);
      /* @noinspection PhpDeprecationInspection */
      $this->iv = mcrypt_create_iv($nonceSize);
    }
    return $this->iv;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(array $args) {
    /* @noinspection PhpDeprecationInspection */
    return mcrypt_encrypt(
      $this->cipher,
      $this->getPassword(),
      serialize($args),
      $this->mode,
      $this->getIv()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($body, $iv) {
    $this->iv = $iv;
    /* @noinspection PhpDeprecationInspection */
    return unserialize(mcrypt_decrypt(
      $this->cipher,
      $this->getPassword(),
      $body,
      $this->mode,
      $this->iv
    ));
  }

}
