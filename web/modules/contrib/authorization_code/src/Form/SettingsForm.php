<?php

namespace Drupal\authorization_code\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for authorization_code settings config.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'authorization_code_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['authorization_code.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('authorization_code.settings');

    $form['seconds_to_expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds to expire'),
      '#default_value' => $config->get('seconds_to_expire'),
      '#min' => 1,
      '#description' => $this->t('The number of seconds each code will remain active.'),
    ];

    $form['max_fetches'] = [
      '#type' => 'number',
      '#title' => $this->t('Max fetches'),
      '#default_value' => $config->get('max_fetches'),
      '#min' => 1,
      '#description' => $this->t('The maximum number of times a code can be fetched and validated against.'),
    ];

    $form['ip_flood_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('ip_flood_threshold'),
      '#default_value' => $config->get('ip_flood_threshold'),
      '#min' => 1,
      '#description' => $this->t('ip_flood_threshold'),
    ];

    $form['ip_flood_window'] = [
      '#type' => 'number',
      '#title' => $this->t('ip_flood_window'),
      '#default_value' => $config->get('ip_flood_window'),
      '#min' => 1,
      '#description' => $this->t('ip_flood_window'),
    ];

    $form['user_flood_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('user_flood_threshold'),
      '#default_value' => $config->get('user_flood_threshold'),
      '#min' => 1,
      '#description' => $this->t('user_flood_threshold'),
    ];

    $form['user_flood_window'] = [
      '#type' => 'number',
      '#title' => $this->t('user_flood_window'),
      '#default_value' => $config->get('user_flood_window'),
      '#min' => 1,
      '#description' => $this->t('user_flood_window'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      $this->config('authorization_code.settings')->set($key, $value);
    }
    $this->config('authorization_code.settings')->save();
    $this->messenger()
      ->addStatus($this->t('The Authorization Code settings have been saved.'));
  }

}
