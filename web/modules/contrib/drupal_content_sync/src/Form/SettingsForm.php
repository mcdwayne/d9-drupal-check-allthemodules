<?php

namespace Drupal\drupal_content_sync\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * DCS general settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drupal_content_sync.settings',
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
    global $base_url;
    $config = $this->config('drupal_content_sync.settings');

    $form['dcs_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#default_value' => $config->get('dcs_base_url'),
      '#description' => $this->t('By default the global base_url provided by Drupal is used for the communication between the DCS backend and Drupal. However, this setting allows you to override the base_url that should be used for the communication.
      Once this is set, all Settings must be reepxorted. This can be done by either saving them, or using <i>drush dcse</i>. Do not include a trailing slash.'),
      '#attributes' => [
        'placeholder' => $base_url,
      ],
    ];

    $form['dcs_enable_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable preview'),
      '#default_value' => $config->get('dcs_enable_preview'),
      '#description' => $this->t('If you want to import content from this site on other sites via the UI ("Manual" import action) and you\'re using custom Preview display modes, check this box to actually export them so they become available on remote sites.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('drupal_content_sync.settings')
      ->set('dcs_base_url', $form_state->getValue('dcs_base_url'))
      ->save();
    $this->config('drupal_content_sync.settings')
      ->set('dcs_enable_preview', boolval($form_state->getValue('dcs_enable_preview')))
      ->save();
  }

}
