<?php

/**
 * @file
 * Contains \Drupal\dblog_conditions\Form\DbLogConditionsSettingsForm.
 */

namespace Drupal\dblog_conditions\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DbLogConditionsSettingsForm
 * @package Drupal\dblog_conditions\Form
 */
class DbLogConditionsSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dblog_conditions_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['dblog_conditions.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dblog_conditions.settings');

    // Build form elements.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['dblog-channels']],
      '#attached' => [
        'library' => ['dblog_conditions/drupal.settings_form'],
      ],
    ];

    $form['channels'] = [
      '#type' => 'details',
      '#title' => $this->t('Channels'),
      '#group' => 'settings',
    ];

    $form['channels']['channels_toggle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Send log to DBLog only for specific channels'),
      '#options' => [
        DBLOG_CONDITIONS_DEFAULT_INCLUDE => $this->t('All channels except the listed channels'),
        DBLOG_CONDITIONS_DEFAULT_EXCLUDE => $this->t('Only the listed channels'),
      ],
      '#default_value' => $config->get('channels_toggle'),
    ];
    $form['channels']['channels_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Listed channels'),
      '#description' => $this->t('Enter one channel name per line, which is usually the module name, ex: migrate '),
      '#default_value' => $config->get('channels_list'),
      '#rows' => 10,
    ];

    // Error level tab
    $description = $this->t('So far, DBLog Conditions only allow simple conditions on channel names.<br /><br />');
    $description .= $this->t('In the future, it would be good to also allow the user to chose which error level to send to DBLog.<br /><br />');

    $form['error_level'] = [
      '#type' => 'details',
      '#title' => $this->t('Error level'),
      '#group' => 'settings',
      '#description' => $description,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Trim the text values.
    $form_state->setValue('channels_list', trim($form_state->getValue('channels_list')));

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('dblog_conditions.settings')
      ->set('channels_toggle', $form_state->getValue('channels_toggle'))
      ->set('channels_list', $form_state->getValue('channels_list'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}
