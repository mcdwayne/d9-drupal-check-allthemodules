<?php

namespace Drupal\tripadvisor_integration\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure TripAdvisor settings.
 */
class TripAdvisorIntegrationAdminForm extends ConfigFormBase {

  /**
   * Constructs a TripAdvisorIntegrationAdminForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tripadvisor_integration_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tripadvisor_integration.admin_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $admin_settings = $this->config('tripadvisor_integration.admin_settings');

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Please read the <a href="https://developer-tripadvisor.com/content-api/" target="_blank">Content API Documentation</a> to find out how obtain an API key and what data is returned from the API.') . '</p>',
    ];

    $form['tripadvisor_integration_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#default_value' => $admin_settings->get('tripadvisor_integration_api_key'),
      '#size' => 35,
      '#maxlength' => 35,
      '#description' => t('This is TripAdvisor API key that will be used to connect to the Content API.'),
    );

    $form['tripadvisor_integration_cache_expiration'] = array(
      '#type' => 'textfield',
      '#title' => t('Cache Expiration'),
      '#default_value' => $admin_settings->get('tripadvisor_integration_cache_expiration'),
      '#size' => 30,
      '#maxlength' => 30,
      '#description' => t('Data from the TripAdvisor Content API will be cached. Set the minimum cache lifetime - default is one hour.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tripadvisor_integration.admin_settings')
      ->set('tripadvisor_integration_api_key', $form_state->getValue('tripadvisor_integration_api_key'))
      ->set('tripadvisor_integration_cache_expiration', $form_state->getValue('tripadvisor_integration_cache_expiration'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
