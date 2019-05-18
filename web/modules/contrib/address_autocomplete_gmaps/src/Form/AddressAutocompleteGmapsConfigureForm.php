<?php

namespace Drupal\address_autocomplete_gmaps\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure the Google API key value.
 */
class AddressAutocompleteGmapsConfigureForm extends ConfigFormBase {

  /**
   * A 'address_autocomplete_gmaps.settings' config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Constructs a AddressAutocompleteGmapsConfigureForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->settings = $config_factory->getEditable('address_autocomplete_gmaps.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_autocomplete_gmaps_configure';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['address_autocomplete_gmaps.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => $this->settings->get('api_key'),
      '#description' => t('Your Google API key.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $api_key = $form_state->getValue('api_key');

    /* Save the configuration */
    $this->settings
      ->set('api_key', $api_key)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
