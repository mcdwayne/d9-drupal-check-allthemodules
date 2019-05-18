<?php

/**
 * @file
 * Contains \Drupal\marketo_forms\Form\MarketoSettingsForm.
 */

namespace Drupal\marketo_forms\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class MarketoSettingsForm extends ConfigFormBase {

  /**
   * Constructs a \Drupal\marketo_forms\MarketoSettingsForm object.
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
    return 'marketo_forms_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['marketo_forms.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('marketo_forms.settings');

    $form['marketo_host_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Marketo Hostname'),
      '#default_value' => $config->get('marketo_host_key'),
      '#description'   => $this->t('The hostname for referencing Marketo code used on the site (Usually in format app-XXXX.marketo.com).'),
      '#required'      => TRUE,
    ];

    $form['marketo_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Marketo API Key'),
      '#default_value' => $config->get('marketo_api_key'),
      '#description'   => $this->t('The Marketo Munchkin ID for the site (usually in format XXX-XXX-XXX).'),
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
    $config = $this->config('marketo_forms.settings');

    // Enable/Disable plugin.
    $config->set('marketo_host_key', $form_state->getValue('marketo_host_key'))
      ->save();
    $config->set('marketo_api_key', $form_state->getValue('marketo_api_key'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
