<?php

/**
 * @file
 * Contains \Drupal\offline_app\Form\AppCacheSettingsForm;
 */

namespace Drupal\offline_app\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class AppCacheSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['offline_app.appcache'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'offline_app_appcache_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('offline_app.appcache');

    $form['expose_read_more'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add "Read more" link for the Offline teaser view mode'),
      '#default_value' => $config->get('expose_read_more'),
    ];

    $form['validate_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URL to validate the manifest'),
      '#default_value' => $config->get('validate_url'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('offline_app.appcache')
      ->set('expose_read_more', $form_state->getValue('expose_read_more'))
      ->set('validate_url', $form_state->getValue('validate_url'))
      ->save();
    Cache::invalidateTags(['rendered']);
    parent::submitForm($form, $form_state);
  }

}
