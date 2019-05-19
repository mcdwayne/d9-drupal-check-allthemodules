<?php

namespace Drupal\user_hash\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Number;

/**
 * Configure user hash settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_hash_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['user_hash.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('user_hash.settings');
    $hash_algorithms = hash_algos();

    $form['algorithm'] = [
      '#type' => 'select',
      '#title' => $this->t('Hash algorithm'),
      '#description' => $this->t('Choose which hash algorithm to use.'),
      '#options' => array_combine($hash_algorithms, $hash_algorithms),
      '#default_value' => $config->get('algorithm'),
    ];

    $form['random_bytes'] = [
      '#type' => 'number',
      '#title' => $this->t('Random bytes'),
      '#description' => $this->t('Configure how many characters to use for the random value at hash generation. See <a href="@crypt_random_bytes" target="_blank">Crypt::randomBytes()</a>.', ['@crypt_random_bytes' => 'https://api.drupal.org/api/drupal/core!lib!Drupal!Component!Utility!Crypt.php/function/Crypt%3A%3ArandomBytes/8']),
      '#default_value' => $config->get('random_bytes'),
      '#min' => 32,
      '#max' => 128,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    Number::validateNumber($form['random_bytes'], $form_state, $form);

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('user_hash.settings')
      ->set('algorithm', $form_state->getValue('algorithm'))
      ->set('random_bytes', $form_state->getValue('random_bytes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
