<?php

namespace Drupal\hp;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure user settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('hp.settings');

    $form['credentials'] = [
      '#type' => 'details',
      '#title' => $this->t('API credentials'),
      '#open' => TRUE,
      '#description' => $this->t('If you do not have a Human Presence account yet, you can <a href="@register_url" target="_blank">register an account</a> with a 14-day free trial to evaluate it.', array('@register_url' => hp_registration_url())) . '<br />' . t('If you forgot your account details or cannot find your API key, email support at drupalsupport@humanpresence.io.'),
    ];
    $form['credentials']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Human Presence monitoring via its JavaScript user interaction tracker.'),
      '#description' => $this->t('Once enabled, Human Presence will protect forms based on the rules you define on the <em>Protected forms</em> tab.'),
      '#default_value' => $config->get('status'),
    ];

    $form['admin_bypass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow the Drupal superuser to bypass Human Presence form protection.'),
      '#description' => $this->t('To allow additional users to bypass form protection, grant them a role assigned the <em>Bypass Human Presence form protection</em> permission.'),
      '#default_value' => $config->get('admin_bypass'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('hp.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('status', $form_state->getValue('status'))
      ->set('admin_bypass', $form_state->getValue('admin_bypass'))
      ->save();
  }

}
