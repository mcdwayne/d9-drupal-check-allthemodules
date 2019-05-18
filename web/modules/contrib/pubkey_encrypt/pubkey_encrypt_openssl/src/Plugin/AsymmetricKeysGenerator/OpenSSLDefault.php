<?php

namespace Drupal\pubkey_encrypt_openssl\Plugin\AsymmetricKeysGenerator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\pubkey_encrypt\Plugin\AsymmetricKeysGeneratorBase;

/**
 * Provides a default asymmetric keys generator based on OpenSSL.
 *
 * @AsymmetricKeysGenerator(
 *   id = "openssl_default",
 *   name = @Translation("OpenSSL Default"),
 *   description = @Translation("RSA-based keys generated via OpenSSL.")
 * )
 */
class OpenSSLDefault extends AsymmetricKeysGeneratorBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function generateAsymmetricKeys() {
    // Generate a Public/Private key pair.
    $config = array(
      "private_key_bits" => (int) $this->getConfiguration()['key_size'],
      "private_key_type" => OPENSSL_KEYTYPE_RSA,
    );
    $res = openssl_pkey_new($config);
    // Extract the private key.
    openssl_pkey_export($res, $private_key, NULL, $config);
    // Extract the public key.
    $public_key = openssl_pkey_get_details($res);
    $public_key = $public_key["key"];

    // Return the keys.
    return array(
      "public_key" => $public_key,
      "private_key" => $private_key,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function encryptWithPublicKey($original_data, $public_key) {
    openssl_public_encrypt($original_data, $encrypted, $public_key);
    return $encrypted;
  }

  /**
   * {@inheritdoc}
   */
  public function decryptWithPrivateKey($encrypted_data, $private_key) {
    openssl_private_decrypt($encrypted_data, $decrypted, $private_key);
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
