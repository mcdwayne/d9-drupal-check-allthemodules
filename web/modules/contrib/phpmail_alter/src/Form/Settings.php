<?php

namespace Drupal\phpmail_alter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form settings controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpmail_alter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phpmail_alter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phpmail_alter.settings');
    module_set_weight('phpmail_alter', 15);

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form["general"]['phpmail'] = [
      '#title' => $this->t('Rewrite drupal PhpMail'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('phpmail'),
      '#description' => $this->t('Better works with non-latin "From" & allow send "text/html" (Recommended).'),
    ];

    $form['general']['from'] = [
      '#title' => $this->t('From Header'),
      '#default_value' => $config->get('from'),
      '#type' => 'textfield',
      '#description' => $this->t('Mail from. Example: <i>MySite @example</i>', ['@example' => '<webmaster@example.com>']),
    ];

    $form['general']['reply'] = [
      '#title' => $this->t('Reply to'),
      '#default_value' => $config->get('reply'),
      '#type' => 'textfield',
      '#description' => $this->t('Email address for reply to email'),
    ];

    $form['general']['debug'] = [
      '#title' => $this->t('Debug mode'),
      '#type' => 'checkbox',
      '#maxlength' => 20,
      '#required' => FALSE,
      '#size' => 15,
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Display full sendmail information with dsm().'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('phpmail_alter.settings');
    $config
      ->set('phpmail', $form_state->getValue('phpmail'))
      ->set('from', $form_state->getValue('from'))
      ->set('reply', $form_state->getValue('reply'))
      ->set('debug', $form_state->getValue('debug'))
      ->save();
  }

}
