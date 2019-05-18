<?php

namespace Drupal\freshchat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FreshchatAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'freshchat_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['freshchat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all configuration required for this form.
    $config = $this->config('freshchat.settings');
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#default_value' => $config->get('token'),
      '#description' => t('Your Freshchat key can be found at https://web.freshchat.com/settings/widget'),
    ];

    $form['logged_in'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show for logged in users'),
      '#default_value' => $config->get('logged_in'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get config.
    \Drupal::configFactory()->getEditable('freshchat.settings')
      ->set('token', $form_state->getValue('token'))
      ->set('logged_in', $form_state->getValue('logged_in'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
