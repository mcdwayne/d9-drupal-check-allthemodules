<?php

namespace Drupal\recruiterbox\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RecruiterBoxSettings.
 *
 * @package Drupal\recruiterbox\Form
 */
class RecruiterBoxSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'recruiterbox.recruiterboxsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recruiter_box_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recruiterbox.recruiterboxsettings');
    $form['recruiterbox_api_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Recruiter box API key'),
      '#description' => $this->t('API key is provided by Recruiter Box. (https://developers.recruiterbox.com/reference#generating-an-api-key)'),
      '#default_value' => $config->get('recruiterbox_api_key'),
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

    $this->config('recruiterbox.recruiterboxsettings')
        ->set('recruiterbox_api_key', $form_state->getValue('recruiterbox_api_key'))
        ->save();
  }

}
