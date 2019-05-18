<?php

namespace Drupal\crypto_miner\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CryptoMinerSettingsForm.
 */
class CryptoMinerSettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'crypto_miner.cryptominersettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crypto_miner_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('crypto_miner.cryptominersettings');
    $form['coinhive_site_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coinhive site key'),
      '#description' => $this->t('Input public Site Key found here: https://coinhive.com/settings/sites'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('coinhive_site_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('crypto_miner.cryptominersettings')
      ->set('coinhive_site_key', $form_state->getValue('coinhive_site_key'))
      ->save();
  }

}
