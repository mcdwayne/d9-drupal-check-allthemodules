<?php

namespace Drupal\user_active_indicator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures User Active Indicator settings.
 */
class UserActiveIndicatorConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_active_indicator_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user_active_indicator.user_active_indicator_settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    $config = $this->config('user_active_indicator.user_active_indicator_settings');
    
    $yesnoOptions = [
      'yes' => t('Yes'),
      'no' => t('No'),
    ];

    $form['options_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General Settings'),
    ];

    $form['options_wrapper']['replace_username'] = [
      '#type' => 'radios',
      '#title' => $this->t('Alter the username Field?'),
      '#required' => TRUE,
      '#options' => $yesnoOptions,
      '#description' => $this->t('This will alter the output of the username field. '),
      '#default_value' => $config->get('replace_username') ? : 'yes',
    ];

    $form['options_wrapper']['replace_user_title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Alter the User Page Title?'),
      '#required' => TRUE,
      '#options' => $yesnoOptions,
      '#description' => $this->t('This will alter the output of the User Page Title. '),
      '#default_value' => $config->get('replace_user_title') ? : 'yes',
    ];
    
    $form['mark_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display Settings'),
    ];

    $form['mark_wrapper']['settings_note'] = [
      '#type' => 'item',
      '#markup' => $this->t('<p>The below settings apply to both the username and User Page Title display.</p>'),
    ];

    $form['mark_wrapper']['show_mark'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show active user mark?'),
      '#required' => TRUE,
      '#options' => $yesnoOptions,
      '#description' => $this->t('Show or hide the active mark. '),
      '#default_value' => $config->get('show_mark') ? : 'yes',
    ];

    $form['mark_wrapper']['show_time'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show time?'),
      '#required' => TRUE,
      '#options' => $yesnoOptions,
      '#description' => $this->t('Show or hide the time value. '),
      '#default_value' => $config->get('show_time') ? : 'yes',
    ];

    $form['mark_wrapper']['active_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message Text'),
      '#description' => $this->t('Shows just before the time value. Example: "Last active: ". Leave blank for no text.'),
      '#default_value' => $config->get('active_message'),
    ];

    $form['mark_wrapper']['no_data_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No Data Message Text'),
      '#description' => $this->t('Shows if no user.data value exists. Example: "- New user". Leave blank for no text.'),
      '#default_value' => $config->get('no_data_message'),
    ];

    $form['time_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Time Settings'),
    ];

    $form['time_wrapper']['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Define Active User'),
      '#required' => TRUE,
      '#description' => $this->t('How much time defines a user as inactive? Value in seconds.'),
      '#default_value' => $config->get('duration') ?: 604800,
    ];

    $form['time_wrapper']['formatting_option'] = [
      '#type' => 'radios',
      '#title' => $this->t('Time Format Option'),
      '#required' => TRUE,
      '#options' => [
        'time_ago' => $this->t('Time Ago'),
        'custom' => $this->t('Custom Format'),
      ],
      '#default_value' => $config->get('formatting_option') ?: 'time_ago',
    ];

    $form['time_wrapper']['custom_date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom Date Formatting'),
      '#required' => true,
      '#description' => t('Set how to format the timestamp. Default: "M d, Y - h:i a".'),
      '#default_value' => $config->get('custom_date_format') ? : 'M d, Y - h:i a',
      '#states' => [
        'visible' => [
          ':input[name="formatting_option"]' => ['value' => 'custom'],
        ],
        'required' => [
          ':input[name="formatting_option"]' => ['value' => 'custom'],
        ],
      ],
    ];
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $values = $form_state->getValues();

    $this->configFactory->getEditable('user_active_indicator.user_active_indicator_settings')
      ->set('replace_username', $values['replace_username'])
      ->set('replace_user_title', $values['replace_user_title'])
      ->set('duration', $values['duration'])
      ->set('show_mark', $values['show_mark'])
      ->set('show_time', $values['show_time'])
      ->set('active_message', $values['active_message'])
      ->set('no_data_message', $values['no_data_message'])
      ->set('formatting_option', $values['formatting_option'])
      ->set('custom_date_format', $values['custom_date_format'])
      ->save();

    parent::submitForm($form, $form_state);

  }

}
