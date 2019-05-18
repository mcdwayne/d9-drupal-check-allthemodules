<?php

namespace Drupal\encrypt_rsa\Plugin\KeyType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\key\Exception\KeyException;
use Drupal\key\Plugin\KeyTypeBase;
use Drupal\key\Plugin\KeyPluginFormInterface;

/**
 * Defines a generic key type for encryption.
 *
 * @KeyType(
 *   id = "pem_private",
 *   label = @Translation("Private key"),
 *   description = @Translation("A public key type to using PEM format."),
 *   group = "encryption",
 *   key_value = {
 *     "plugin" = "textarea_field"
 *   }
 * )
 */
class PemPrivateFormatKeyType extends PemFormatKeyTypeBase implements KeyPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'passphrase' => '',
    ];
  }

  /**
   * The passphrase, ONLY used to validate the key.
   *
   * @var string
   */
  private $passphrase;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['disclaimer'] = [
      '#type' => 'item',
      '#markup' => $this->t('You should not upload a private key, as this is not secure. If your encryption process requires you to store such key, make sure you protect it through a passphrase. Also using a provider like Lockr.io or AWS KMS may add additional security layers.'),
    ];

    $form['passphrase'] = [
      '#title' => $this->t('Private key passphrase'),
      '#type' => 'password',
      '#description' => $this->t('Private key passphrase. This is only used to validate the key on submitting this form, but to improve the security it will NOT be saved.'),
      '#default_value' => $this->getConfiguration()['passphrase'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do NOT store the passphrase.
    $form_state->setValue('passphrase', '');
    $form_state->unsetValue('disclaimer');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateKeyValue(array $form, FormStateInterface $form_state, $key_value) {

    // Set the passphrase in the private property so we can access its value in
    // getKeyDetails() method.
    $this->passphrase = $form_state->getValue('passphrase');

    // Let the base abstract class do the validation.
    parent::validateKeyValue($form, $form_state, $key_value);

    // Better safe than sorry, empty the private property.
    $this->passphrase = NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getKeyDetails($key_value) {
    $key = openssl_get_privatekey($key_value, $this->passphrase);
    return $key ? openssl_pkey_get_details($key) : FALSE ;
  }

}
