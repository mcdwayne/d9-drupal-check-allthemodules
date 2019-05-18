<?php

namespace Drupal\pubkey_encrypt_phpseclib\Plugin\AsymmetricKeysGenerator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorBase;
use phpseclib\Crypt\RSA;

/**
 * Provides an asymmetric keys generator based on PHPSecLib.
 *
 * @AsymmetricKeysGenerator(
 *   id = "phpseclib",
 *   name = @Translation("PHPSecLib"),
 *   description = @Translation("RSA-based keys generated via PHPSecLib.")
 * )
 */
class PHPSecLib extends AsymmetricKeysGeneratorBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function generateAsymmetricKeys() {
    $rsa = new RSA();
    $keys = $rsa->createKey($this->getConfiguration()['key_size']);

    // Return the keys.
    return array(
      "public_key" => $keys['publickey'],
      "private_key" => $keys['privatekey'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function encryptWithPublicKey($original_data, $public_key) {
    $rsa = new RSA();
    $rsa->loadKey($public_key);
    $encrypted = $rsa->encrypt($original_data);
    return $encrypted;
  }

  /**
   * {@inheritdoc}
   */
  public function decryptWithPrivateKey($encrypted_data, $private_key) {
    $rsa = new RSA();
    $rsa->loadKey($private_key);
    $decrypted = $rsa->decrypt($encrypted_data);
    return $decrypted;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_size' => '2048',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['key_size'] = array(
      '#type' => 'select',
      '#title' => t('Key size in bits'),
      '#options' => [
        '2048' => '2048',
        '4096' => '4096',
      ],
      '#default_value' => $this->getConfiguration()['key_size'],
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $key_size = $form_state->getValue('key_size');
    if ($key_size < 2048) {
      $form_state->setErrorByName('key_size', 'Key size too small.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

}
