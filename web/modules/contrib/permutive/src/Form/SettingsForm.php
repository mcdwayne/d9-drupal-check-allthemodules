<?php

namespace Drupal\permutive\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['permutive.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Your Permutive API key'),
      '#default_value' => $this->config('permutive.settings')->get('api_key'),
      '#maxlength' => 128,
      '#size' => 64,
      '#weight' => '0',
    ];
    $form['project_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Project Id'),
      '#description' => $this->t('Your Permutive project id'),
      '#default_value' => $this->config('permutive.settings')->get('project_id'),
      '#maxlength' => 128,
      '#size' => 64,
      '#weight' => '0',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('permutive.settings')
      ->set('api_key', $form_state->getValue(['api_key']))
      ->set('project_id', $form_state->getValue('project_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
