<?php

/**
 * @file
 * Contains \Drupal\hubspot_forms\Form\HubspotSettingsForm.
 */

namespace Drupal\hubspot_forms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class HubspotSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\hubspot_forms\HubspotSettingsForm object.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hubspot_forms_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hubspot_forms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('hubspot_forms.settings');

    $form['hubspot_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Hubspot API Key'),
      '#default_value' => $config->get('hubspot_api_key'),
      '#description'   => $this->t('Please use <strong>demo</strong> to load example forms.
        Generate a <a href="https://app.hubspot.com/keys/get" target="_blank">new key</a>.
        Make sure to clear Drupal cache after you change API Key.'),
      '#required'      => TRUE,
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
    $config = $this->config('hubspot_forms.settings');

    // Enable/Disable plugin.
    $config->set('hubspot_api_key', $form_state->getValue('hubspot_api_key'))
      ->save();

    // Clear Drupal cache.
    \Drupal::cache()->delete('hubspot_forms');

    parent::submitForm($form, $form_state);

  }

}
