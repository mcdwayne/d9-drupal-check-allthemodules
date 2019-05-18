<?php

namespace Drupal\loggable\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LoggableSettingsForm.
 */
class LoggableSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'loggable.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'loggable_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('loggable.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('Enter your Loggable API key. This can be found <a href=":url">here</a>.', [':url' => $config->get('domain') . '/key']),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];
    $form['channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel ID'),
      '#description' => $this->t('Enter the ID of the channel you wish to store events in. This can be found <a href=":url">here</a>.', [':url' => $config->get('domain') . '/channels']),
      '#default_value' => $config->get('channel_id'),
      '#required' => TRUE,
    ];
    $form['domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Domain'),
      '#description' => $this->t('Enter the domain where your Loggable instance is located. This should not change unless you are instructed.'),
      '#default_value' => $config->get('domain'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('loggable.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('channel_id', $form_state->getValue('channel_id'))
      ->set('domain', $form_state->getValue('domain'))
      ->save();
  }

}
