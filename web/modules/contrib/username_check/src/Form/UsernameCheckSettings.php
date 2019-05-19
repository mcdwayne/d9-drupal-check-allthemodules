<?php

/**
 * @file
 * Contains \Drupal\username_check\Form\UsernameCheckSettings.
 */

namespace Drupal\username_check\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class UsernameCheckSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'username_check_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('username_check.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['username_check.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [];
    $config = \Drupal::config('username_check.settings');
    $form['username_check_mode'] = [
      '#type' => 'radios',
      '#title' => t('Check for usernames pre-existing in system:'),
      '#options' => [
        'auto' => t('On - executes when user leaves username field or upon timer end'),
        'off' => t('Off - No Username checking'),
      ],
      '#default_value' => $config->get('username_check_mode'),
    ];

    $form['username_check_mail_mode'] = [
      '#type' => 'radios',
      '#title' => t('Check for E-mail addresses pre-existing in system:'),
      '#options' => [
        'auto' => t('On - executes when user leaves e-mail field or upon timer end'),
        'off' => t('Off - No E-mail address checking'),
      ],
      '#default_value' => $config->get('username_check_mail_mode'),
    ];

    $form['username_check_delay'] = [
      '#type' => 'textfield',
      '#title' => t('Timer threshold:'),
      '#description' => t('Threshold in seconds (ex: 0.5, 1) for the check to happen.'),
      '#default_value' => $config->get('username_check_delay'),
    ];




    return parent::buildForm($form, $form_state);
  }

}
