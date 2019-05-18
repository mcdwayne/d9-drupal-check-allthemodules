<?php

namespace Drupal\leaflet_maptiler\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LeafletMaptilerSettingsForm.
 */
class LeafletMaptilerSettingsForm extends ConfigFormBase {

  /**
   * The cache object associated with the default bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDefault;

  /**
   * Constructs a new LeafletMaptilerSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_default
   *   The cache object associated with the default bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_default) {
    parent::__construct($config_factory);
    $this->cacheDefault = $cache_default;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'leaflet_maptiler.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leaflet_maptiler_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /*
     * Loads the maptiler configurations.
     */
    $config = $this->config('leaflet_maptiler.settings');
    /*
     * Adds field for the Maptiler API Key.
     */
    $form['leaflet_maptiler_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maptiler API Key'),
      '#description' => $this->t('Enter API Key for Maptiler.'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('leaflet_maptiler_api_key'),
    ];
    /*
     * Adds field for setting the Maptiler layers to use.
     */
    $form['leaflet_maptiler_layers'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maptiler layers'),
      '#description' => $this->t('Insert the name of the Maptiler Layers in small letters and separated by comma (example: basic, bright, hybrid, etc.)'),
      '#maxlength' => 255,
      '#size' => 64,
      '#default_value' => $config->get('leaflet_maptiler_layers'),
    ];
    /*
     * Adds fieldset for the geocoder external library settings.
     */
    $form['leaflet_maptiler_geocode_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Geocoder external library'),
      '#tree' => TRUE,
    ];
    /*
     * Adds field for setting the Geocoder js library url.
     */
    $form['leaflet_maptiler_geocode_fieldset']['leaflet_maptiler_geocoder_js'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geocoder JS library url'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => $config->get('leaflet_maptiler_geocoder_js'),
    ];
    /*
     * Adds field for setting the Geocoder css library url.
     */
    $form['leaflet_maptiler_geocode_fieldset']['leaflet_maptiler_geocoder_css'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Geocoder css library url'),
      '#size' => 60,
      '#maxlength' => 255,
      '#default_value' => $config->get('leaflet_maptiler_geocoder_css'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /*
     * Saves the configuration.
     */
    $this->config('leaflet_maptiler.settings')
      ->set('leaflet_maptiler_api_key', $form_state->getValue('leaflet_maptiler_api_key'))
      ->set('leaflet_maptiler_layers', $form_state->getValue('leaflet_maptiler_layers'))
      ->set('leaflet_maptiler_geocoder_js', $form_state->getValue('leaflet_maptiler_geocode_fieldset')['leaflet_maptiler_geocoder_js'])
      ->set('leaflet_maptiler_geocoder_css', $form_state->getValue('leaflet_maptiler_geocode_fieldset')['leaflet_maptiler_geocoder_css'])
      ->save();
    /*
     * Refresh the cache table or hook_leaflet_map_get_info() won't be called.
     */
    drupal_flush_all_caches();
  }

}
