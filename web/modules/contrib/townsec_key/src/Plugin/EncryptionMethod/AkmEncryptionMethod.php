<?php

namespace Drupal\townsec_key\Plugin\EncryptionMethod;

use RuntimeException;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use TownsendSecurity\Akm;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Drupal\encrypt\Plugin\EncryptionMethodPluginFormInterface;

/**
 * @EncryptionMethod(
 *   id = "akm",
 *   title = @Translation("AKM Streaming Encryption")
 * )
 */
class AkmEncryptionMethod extends EncryptionMethodBase
implements EncryptionMethodInterface,
EncryptionMethodPluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key) {
    $config = $this->getConfiguration();
    $key_name = $config['key_name'];

    $akm = \Drupal::service('townsec_key.akm');

    try {
      return $akm->encrypt($text, $key_name);
    }
    catch (RuntimeException $e) {
      watchdog_exception('townsec_key', $e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    $akm = \Drupal::service('townsec_key.akm');

    try {
      return $akm->decrypt($text);
    }
    catch (RuntimeException $e) {
      watchdog_exception('townsec_key', $e);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['key_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Name'),
      '#maxlength' => 40,
      '#default_value' => $config['key_name'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $key_name = $values['key_name'];
    $akm = \Drupal::service('townsec_key.akm');

    try {
      $result = $akm->decrypt($akm->encrypt('test', $key_name));
    }
    catch (RuntimeException $e) {
      $result = FALSE;
    }

    if ($result !== 'test') {
      $form_state->setErrorByName('', 'AKM encryption failed.');
    }
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
  public function calculateDependencies() {
    $configFactory = \Drupal::configFactory();
    if (count($configFactory->listAll('townsec_key.akm_server.')) < 1) {
      return [$this->t('AKM encryption method requires at least one AKM server.')];
    }

    return [];
  }

}
