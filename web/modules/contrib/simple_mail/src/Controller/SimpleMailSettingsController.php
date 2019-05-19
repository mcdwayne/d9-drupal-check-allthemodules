<?php

namespace Drupal\simple_mail\Controller;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Returns responses for Simple Mail module routes.
 */
class SimpleMailSettingsController extends ConfigFormBase {

  /**
   * Get a value from the retrieved form settings array.
   */
  public function getFormSettingsValue($form_settings, $form_id) {
    // If there are settings in the array and the form ID already has a setting,
    // return the saved setting for the form ID.
    if (!empty($form_settings) && isset($form_settings[$form_id])) {
      return $form_settings[$form_id];
    }
    // Default to false.
    else {
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_mail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_mail_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['queue_enabled'] = array(
      '#type' => 'select',
      '#title' => t('Simple Mail Queue'),
      '#description' => t('You can disable the queue functionality by setting this option to Disabled.'),
      '#default_value' => \Drupal::config('simple_mail.settings')->get('queue_enabled'),
      '#options' => array(
        0 => t('Disabled'),
        1 => t('Enabled'),
      ),
    );
    $form['override'] = array(
      '#type' => 'email',
      '#title' => t('E-mail override address'),
      '#placeholder' => 'john.doe@example.com',
      '#description' => t('Enter an e-mail address to have all system emails redirected to it. If empty, e-mail will be delivered normally, to the intended recipient.'),
      '#default_value' => \Drupal::config('simple_mail.settings')->get('override'),
    );

    // Store the keys we want to save in configuration when form is submitted.
    $keys_to_save = array_keys($form);
    foreach ($keys_to_save as $key => $key_to_save) {
      if (strpos($key_to_save, '#') !== FALSE) {
        unset($keys_to_save[$key]);
      }
    }
    $form_state->setStorage(['keys' => $keys_to_save]);

    // For now, manually add submit button. Hopefully, by the time D8 is
    // released, there will be something like system_settings_form() in D7.
    $form['actions']['#type'] = 'container';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('simple_mail.settings');
    $storage = $form_state->getStorage();

    // Save all the Simple Mail configuration items from $form_state.
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, $storage['keys'])) {
        $config->set($key, $value);
      }
    }

    $config->save();

    // Tell the user the settings have been saved.
    drupal_set_message(t('The configuration options have been saved.'));
  }

}
