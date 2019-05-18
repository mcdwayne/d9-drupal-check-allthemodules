<?php

namespace Drupal\campaignmonitor_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure campaignmonitor settings for this site.
 */
class CampaignMonitorRegistrationAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'campaignmonitor_registration_admin_settings';
  }

  /**
   *
   */
  protected function getEditableConfigNames() {
    return ['campaignmonitor_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_registration.settings');

    $form['checkbox_text'] = [
      '#type' => 'textarea',
      '#title' => t('Registration text'),
      '#description' => t('The text to use by the checkbox that reveals the newsletter lists'),
      '#default_value' => $config->get('checkbox_text'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('campaignmonitor_registration.settings');
    $config
      ->set('checkbox_text', $form_state->getValue('checkbox_text'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
