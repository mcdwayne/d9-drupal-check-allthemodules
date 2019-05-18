<?php

/**
 * @file
 * Contains \Drupal\analytics\Form\AnalyticsSettingsForm.
 */

namespace Drupal\analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class AnalyticsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'analytics_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('analytics.settings');
    $config->set('analytics_privacy_dnt', $form_state->getValue('analytics_privacy_dnt'))->save();
    $config->set('analytics_privacy_anonymize_ip', $form_state->getValue('analytics_privacy_anonymize_ip'))->save();
    $config->set('analytics_cache_urls', $form_state->getValue('analytics_cache_urls'))->save();
    $config->set('analytics_disable_page_build', $form_state->getValue('analytics_disable_page_build'))->save();
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['analytics.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $config = $this->config('analytics.settings');
    $form['privacy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Privacy'),
    ];
    $form['privacy']['analytics_privacy_dnt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Respect Do Not Track (DNT) cookies.'),
      '#default_value' => $config->get('analytics_privacy_dnt', TRUE),
    ];
    $form['privacy']['analytics_privacy_anonymize_ip'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Anonymize IP addresses.'),
      '#default_value' => $config->get('analytics_privacy_anonymize_ip', FALSE),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#open' => FALSE,
    ];
    $form['advanced']['analytics_cache_urls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache files locally where possible.'),
      '#default_value' => $config->get('analytics_cache_urls', FALSE),
    ];
    $form['advanced']['analytics_disable_page_build'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable default analytics service rendering in hook_page_bottom().'),
      '#default_value' => $config->get('analytics_disable_page_build', FALSE),
    ];

    return parent::buildForm($form, $form_state);
  }

}
