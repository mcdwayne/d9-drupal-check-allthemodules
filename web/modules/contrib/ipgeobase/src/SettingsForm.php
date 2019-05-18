<?php

namespace Drupal\ipgeobase;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'ipgeobase.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ipgeobase_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ipgeobase.settings');

    $default_cache_ttl = $config->get('cache_ttl');
    $default_cache_ttl = isset($default_cache_ttl) ? $default_cache_ttl : 86400;

    $form['cache_ttl'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cache entry TTL (seconds)'),
      '#default_value' => $default_cache_ttl,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ipgeobase.settings')
      ->set('cache_ttl', $values['cache_ttl'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}