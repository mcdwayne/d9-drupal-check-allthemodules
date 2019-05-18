<?php

namespace Drupal\encrypt_rsa\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\key\Exception\KeyException;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Defines a generic key type for encryption.
 */
abstract class PemFormatKeyTypeBase extends KeyTypeBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'key_size' => 1024,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Define the most common key size options.
    $key_size_options = [
      '128' => 128,
      '256' => 256,
      '1024' => 1024,
      '2048' => 2048,
    ];

    $key_size = $this->getConfiguration()['key_size'];
    $key_size_other_value = '';
    if (!in_array($key_size, $key_size_options)) {
      $key_size_other_value = $key_size;
      $key_size = 'other';
    }

    $form['key_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Key size'),
      '#description' => $this->t('The size of the key in bits, with 8 bits per byte.'),
      '#options' => $key_size_options + ['other' => $this->t('Other')],
      '#default_value' => $key_size,
      '#required' => TRUE,
    ];
    $form['key_size_other_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key size (other value)'),
      '#title_display' => 'invisible',
      '#description' => $this->t('Enter a custom key size in bits.'),
      '#default_value' => $key_size_other_value,
      '#maxlength' => 20,
      '#size' => 20,
      '#states' => [
        'visible' => [
          'select[name="key_type_settings[key_size]"]' => ['value' => 'other'],
        ],
        'required' => [
          'select[name="key_type_settings[key_size]"]' => ['value' => 'other'],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // If 'Other' was selected for the key size, use the custom entered value.
    $key_size = $form_state->getValue('key_size');
    if ($key_size == 'other') {
      $form_state->setValue('key_size', $form_state->getValue('key_size_other_value'));
    }
    $form_state->unsetValue('key_size_other_value');

    // Cast key_size to integer.
    $form_state->setValue('key_size', (int) $form_state->getValue('key_size'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public static function generateKeyValue(array $configuration) {
    throw new KeyException('Create .pem format keys is forbidden, use the openssl CLI tool for better security.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {
    if (!$form_state->getValue('key_size')) {
      return;
    }

    // Fetch key details.
    $key_details = $this->getKeyDetails($key_value);
    if (!$key_details || !$key_details['bits']) {
      $form_state->setErrorByName('key_value', $this->t('An error occurred on fetching key details.'));
      return;
    }

    // Validate the key size.
    if ((int) $form_state->getValue('key_size') !== $key_details['bits']) {
      $form_state->setErrorByName('key_size', $this->t('The selected key size does not match the actual size of the key.'));
    }

  }

  /**
   * Get key details.
   *
   * Return key details as the output of openssl_pkey_get_details().
   *
   * @param string $key_value
   *   The key value in PEM format.
   *
   * @return array|false
   *   An array compatible with openssl_pkey_get_details() output, or FALSE on
   *   error.
   *
   * @see openssl_pkey_get_details()
   */
  abstract protected function getKeyDetails($key_value);

}
