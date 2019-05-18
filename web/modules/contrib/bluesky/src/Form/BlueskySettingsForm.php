<?php

namespace Drupal\bluesky\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Contains the main module settings configuration form.
 */
class BlueskySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bluesky_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Default settings.
    $config = $this->config('bluesky.settings');

    // Subdomain field.
    $form['subdomain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BlueSky Subdomain'),
      '#default_value' => $config->get('bluesky.subdomain'),
      '#description' => $this->t('The subdomain of your BlueSky account. Can be found by viewing the first part of your bluesky url. i.e https://<strong>example</strong>.blueskymeeting.com'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('subdomain')) == 0) {
      $form_state->setErrorByName('subdomain', $this->t('The BlueSky Subdomain can not be blank.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bluesky.settings');
    $config->set('bluesky.subdomain', $form_state->getValue('subdomain'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'bluesky.settings',
    ];
  }

}
