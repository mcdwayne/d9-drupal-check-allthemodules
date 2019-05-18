<?php

namespace Drupal\message_time_interval\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MessageTimeSettingsForm.
 *
 * @package Drupal\message_time_interval\Form.
 */
class MessageTimeIntervalSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'message_time_interval_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['message_time_interval.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->config('message_time_interval.settings');

    // General message time form settings.
    $form['message_time_interval_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Message Time Settings'),
      '#open' => TRUE,
    ];
    $form['message_time_interval_settings']['message_time_interval_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled Message Time'),
      '#default_value' => $settings->get('message_time_interval_enabled'),
      '#description' => $this->t('To enabled the message disappear functionality from entire system'),
    ];

    $form['message_time_interval_settings']['message_time_interval'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message Display Time'),
      '#description' => $this->t('Duration Time Format(10000ms = 10 sec)'),
      '#default_value' => $settings->get('message_time_interval'),
      '#states' => [
        'required' => [
          ':input[name="message_time_interval_enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $effect = [
      'fadeOut' => 'Fade Out',
      'slideUp' => 'Slide Up',
      'hide' => 'Hide',
    ];

    $form['message_time_interval_settings']['message_time_interval_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effect'),
      '#options' => $effect,
      '#default_value' => $settings->get('message_time_interval_effect'),
      '#states' => [
        'required' => [
          ':input[name="message_time_interval_enabled"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->config('message_time_interval.settings')
      ->set('message_time_interval_value', TRUE)
      ->save();
    if (!is_numeric($form_state->getValue('message_time_interval'))) {
      $this->config('message_time_interval.settings')
        ->set('message_time_interval_value', FALSE)
        ->save();
      $form_state->setErrorByName('message_time_interval', $this->t('Message Time Interval must be numberic.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('message_time_interval.settings')
      ->set('message_time_interval_enabled', $form_state->getValue('message_time_interval_enabled'))
      ->set('message_time_interval', $form_state->getValue('message_time_interval'))
      ->set('message_time_interval_effect', $form_state->getValue('message_time_interval_effect'))
      ->save();
    drupal_set_message($this->t('Message time interval saved successfully.'), 'status');
  }

}
