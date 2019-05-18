<?php

namespace Drupal\forms_steps\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormsStepsSettingsForm.
 *
 * @package Drupal\forms_steps\Form
 */
class FormsStepsSettingsForm extends ConfigFormBase {

  protected $config = 'forms_steps.settings';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'forms_steps.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'forms_steps_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->config);

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Message description'),
      '#defaut_value' => $config->get('message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config($this->config)
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
