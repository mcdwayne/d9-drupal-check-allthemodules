<?php

namespace Drupal\webform_epetition\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WebformEpetitionConfigForm.
 */
class WebformEpetitionConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'webform_epetition.webformepetitionconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_epetition_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webform_epetition.webformepetitionconfig');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#description' => $this->t('Add API Key of selected service'),
      '#maxlength' => 300,
      '#size' => 200,
      '#default_value' => $config->get('api_key'),
    ];
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API URL'),
      '#description' => $this->t('The base URL, include https://, exclude backslash at end'),
      '#maxlength' => 300,
      '#size' => 200,
      '#default_value' => $config->get('api_url'),
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

    $this->config('webform_epetition.webformepetitionconfig')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->save();
  }

}
