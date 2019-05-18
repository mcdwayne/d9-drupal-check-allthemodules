<?php

namespace Drupal\finteza_analytics\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure finteza_analytics settings for this site.
 */
class FintezaAnalyticsTrackingSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormId().
   */
  public function getFormId() {
    return 'finteza_analytics_tracking_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'finteza_analytics.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL) {

    // Create config object.
    $config = $this->config('finteza_analytics.settings');

    $website_id = $config->get('tracking_settings.finteza_analytics_website_id');
    $website_id_description = "";

    if (empty($website_id)) {
      $website_id_description = $this->t(
        "<a href='@registration_url' target='_blank'>Register</a> an account in Finteza",
        finteza_analytics_urls()
      );
    }

    $form['tracking_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Tracking settings'),
      '#open' => TRUE,
    ];

    $form['tracking_settings']['finteza_analytics_website_id'] = [
      '#default_value' => $website_id,
      '#title' => $this->t('Website ID'),
      '#type' => 'textfield',
      '#description' => $website_id_description,
    ];

    $form['tracking_settings']['track_hash'] = [
      '#default_value' => $config->get('tracking_settings.track_hash'),
      '#title' => $this->t('Track hash changes in the address bar'),
      '#type' => 'checkbox',
    ];

    $form['tracking_settings']['track_links'] = [
      '#default_value' => $config->get('tracking_settings.track_links'),
      '#title' => $this->t('Track outbound links'),
      '#type' => 'checkbox',
    ];

    $form['tracking_settings']['time_on_page'] = [
      '#default_value' => $config->get('tracking_settings.time_on_page'),
      '#title' => $this->t('Exact time on website'),
      '#type' => 'checkbox',
    ];

    $form['tracking_settings']['dont_track_admins'] = [
      '#default_value' => $config->get('tracking_settings.dont_track_admins'),
      '#title' => $this->t('Disable tracking of admin visits'),
      '#type' => 'checkbox',
    ];

    $form['tracking_settings']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Changes'),
    ];

    $form['tracking_settings']['save']['#attributes']['class'][] = 'button--primary';

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('finteza_analytics.settings');
    $finteza_analytics_website_id = $form_state->getValue('finteza_analytics_website_id');

    $track_hash = $form_state->getValue('track_hash');
    $track_links = $form_state->getValue('track_links');
    $time_on_page = $form_state->getValue('time_on_page');
    $dont_track_admins = $form_state->getValue('dont_track_admins');

    $config
      ->set('tracking_settings.finteza_analytics_website_id', $finteza_analytics_website_id)
      ->set('tracking_settings.track_hash', $track_hash)
      ->set('tracking_settings.track_links', $track_links)
      ->set('tracking_settings.time_on_page', $time_on_page)
      ->set('tracking_settings.dont_track_admins', $dont_track_admins)
      ->save();

    Cache::invalidateTags(['FINTEZA_ANALYTICS_HELP', 'FINTEZA_ANALYTICS_SCRIPT']);

    parent::submitForm($form, $form_state);
  }

}
