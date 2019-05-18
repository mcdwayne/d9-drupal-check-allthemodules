<?php

namespace Drupal\cove_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CoveSettingsForm.
 *
 * @package Drupal\cove_api\Form
 */
class CoveSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'cove_api.covesettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cove_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cove_api.covesettings');
    $form['pbs_cove_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PBS COVE API Key'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('pbs_cove_api_key'),
    ];
    $form['pbs_cove_api_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PBS COVE API Secret'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('pbs_cove_api_secret'),
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

    $this->config('cove_api.covesettings')
      ->set('pbs_cove_api_key', $form_state->getValue('pbs_cove_api_key'))
      ->set('pbs_cove_api_secret', $form_state->getValue('pbs_cove_api_secret'))
      ->save();
  }

}
