<?php

namespace Drupal\simpleautologout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides settings for simple autologout module.
 */
class SimpleAutologoutSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['simpleautologout.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simpleautologout_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('simpleautologout.settings');
    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout'),
      '#default_value' => $config->get('timeout'),
      '#size' => 8,
      '#weight' => -10,
      '#description' => $this->t('The length of inactivity time, in seconds, before automated log out.  Must be 60 seconds or greater.'),
    ];

    $form['max_timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Max timeout setting'),
      '#default_value' => $config->get('max_timeout'),
      '#size' => 10,
      '#maxlength' => 12,
      '#weight' => -8,
      '#description' => $this->t('The maximum logout threshold time that use to logout users which have stalled session present in database.'),
    ];

    $seconds = [
      60 => 60,
      120 => 120,
      180 => 180,
      240 => 240,
      300 => 300,
      180 => 180,
      240 => 240,
      300 => 300,
      600 => 600,
      900 => 900,
      1200 => 1200,
      1800 => 1800,
    ];

    $form['timeout_refresh_rate'] = [
      '#type' => 'select',
      '#title' => $this->t('Time Interval'),
      '#options' => $seconds,
      '#default_value' => $config->get('timeout_refresh_rate'),
      '#weight' => -8,
      '#description' => $this->t('The time interval, in seconds, after which simple autologot checks for timeout.'),
    ];

    $form['redirect_url']  = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URL at logout'),
      '#default_value' => $config->get('redirect_url'),
      '#size' => 40,
      '#description' => $this->t('Send users to this internal page when they are logged out.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    
    $timeout = $values['timeout'];
    // Validate timeout.
    if ($timeout < 60) {
      $form_state->setErrorByName('timeout', $this->t('The timeout value must be an integer 60 seconds or greater.'));
    }
    elseif (!is_numeric($timeout)) {
      $form_state->setErrorByName('timeout', $this->t('The timeout must be an integer greater than 60 seconds'));
    }

    $redirect_url = $values['redirect_url'];

    // Validate redirect url.
    if (strpos($redirect_url, '/') !== 0) {
      $form_state->setErrorByName('redirect_url', $this->t("The user-entered string :redirect_url must begin with a '/'", [':redirect_url' => $redirect_url]));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $simple_autologout_settings = $this->config('simpleautologout.settings');

    $simple_autologout_settings->set('timeout', $values['timeout'])
      ->set('max_timeout', $values['max_timeout'])
      ->set('timeout_refresh_rate', $values['timeout_refresh_rate'])
      ->set('redirect_url', $values['redirect_url'])
      ->save();
  }

}
