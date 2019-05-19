<?php

/**
 * @file
 * Contains \Drupal\extlink\Form\StatuscakeAdminSettingsForm.
 */

namespace Drupal\statuscake_push_monitoring\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Displays the extlink settings form.
 */
class StatuscakeAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'statuscake_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['statuscake_push_monitoring_url'] = array(
      '#type' => 'url',
      '#title' => t('URL to PUSH to statuscake servers'),
      '#default_value' => \Drupal::config('statuscake_push_monitoring.settings')->get('statuscake_push_monitoring_url'),
      '#size' => 70,
      '#description' => t("The URL from statuscake.com settings page"),
      '#required' => TRUE,
    );
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
    $values = $form_state->getValues();

    \Drupal::configFactory()->getEditable('statuscake_push_monitoring.settings')
      ->set('statuscake_push_monitoring_url', $values['statuscake_push_monitoring_url'])
      ->save();

    parent::SubmitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['statuscake_push_monitoring.settings'];
  }

}
