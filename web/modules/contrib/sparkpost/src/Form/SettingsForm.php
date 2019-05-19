<?php

namespace Drupal\sparkpost\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class SettingsForm.
 *
 * @package Drupal\sparkpost\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The plugin id for our mailer.
   */
  const MAIL_KEY = 'sparkpost_mail';

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sparkpost.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sparkpost.settings');
    $key = $config->get('api_key');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#default_value' => $key,
      '#description' => $this->t('Create or grab your API key from the <a href="@url">Sparkpost settings.</a>', [
        '@url' => 'https://app.sparkpost.com/account/credentials',
      ]),
    ];
    if ($key) {
      // Check if we want to warn the user about the fact that Sparkpost is not
      // being used as mail system.
      if (\Drupal::moduleHandler()->moduleExists('mailsystem')) {
        // Check config for mailsystem.
        $default_sender = $this->config('mailsystem.settings')
          ->get('defaults.sender');
        if ($default_sender != $this::MAIL_KEY) {
          drupal_set_message($this->t('It seems you are using the mailsystem module to control your mail, but the default sender is not set to Sparkpost. If this is not on purpose you should probably <a href="@url">adjust the settings at this page.</a>', [
            '@url' => Url::fromRoute('mailsystem.settings')->toString(),
          ]), 'warning');
        }
      }
      else {
        // See if the config sets it to something else than sparkpost.
        $config_collection = 'system.mail';
        $config_key = 'interface.default';
        $default_system = $this->config($config_collection)
          ->get($config_key);
        if ($default_system != $this::MAIL_KEY) {
          drupal_set_message($this->t('You seem to be using %system as your mail system instead of %sparkpost. If this is not on purpose, you should change the configration for %config_key in %config to %sparkpost.', [
            '%system' => $default_system,
            '%sparkpost' => $this::MAIL_KEY,
            '%config_key' => $config_key,
            '%config' => $config_collection,
          ]), 'warning');
        }
      }
      $form['options'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Email options'),
        '#collapsible' => TRUE,
      ];
      $form['options']['debug'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Debug'),
        '#default_value' => $config->get('debug') ? $config->get('debug') : TRUE,
        '#description' => $this->t('If selected, exceptions will be sent over to watchdog.'),
      ];
      $form['options']['sender'] = [
        '#type' => 'email',
        '#title' => $this->t('From address'),
        '#default_value' => $config->get('sender'),
        '#description' => $this->t('The sender email address. If this address has not been verified, messages will not be sent. This address will appear in the "from" field, and any emails sent through Sparkpost with a "from" address will have that address moved to the Reply-To field.'),
        '#required' => TRUE,
      ];
      $form['options']['sender_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('From name'),
        '#default_value' => $config->get('sender_name'),
        '#description' => $this->t('Optionally enter a from name to be used.'),
      ];
      $options = ['' => $this->t('-- Select --')];
      $formats = filter_formats();
      foreach ($formats as $key => $format) {
        $options[$key] = $format->label();
      }
      $form['options']['format'] = [
        '#title' => $this->t('Input format'),
        '#type' => 'select',
        '#options' => $options,
        '#description' => $this->t('If selected, the input format to apply to the message body before sending to the Sparkpost API.'),
        '#default_value' => $config->get('format'),
      ];
      $form['options']['async'] = [
        '#title' => $this->t('Send asynchronous'),
        '#default_value' => $config->get('async'),
        '#type' => 'checkbox',
        '#description' => $this->t('If selected, the mails will not be sent right away, but queued and possibly sent with cron or drush.'),
      ];
      $form['options']['skip_cron'] = [
        '#type' => 'checkbox',
        '#title' => t('Skip queue on cron'),
        '#default_value' => $config->get('skip_cron'),
        '#description' => t('If selected, the mail queue will not be processed by cron runs. Use this only if you manually run the queue with drush (for example).'),
        '#states' => [
          'visible' => [
            ':input[name="async"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sparkpost.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('debug', $form_state->getValue('debug'))
      ->set('sender_name', $form_state->getValue('sender_name'))
      ->set('sender', $form_state->getValue('sender'))
      ->set('format', $form_state->getValue('format'))
      ->set('async', $form_state->getValue('async'))
      ->set('skip_cron', $form_state->getValue('skip_cron'))
      ->save();
  }

}
