<?php

namespace Drupal\healthz_geocoder\Plugin\HealthzCheck;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geocoder\GeocoderInterface;
use Drupal\geocoder\ProviderPluginManager;
use Drupal\healthz\Plugin\HealthzCheckBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Geocoder\Model\AddressCollection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if API keys for Geocoder plugins are valid.
 *
 * @HealthzCheck(
 *   id = "healthz_geocoder",
 *   title = @Translation("Geocoder API key check"),
 *   description = @Translation("Checks if API keys for Geocoder plugins are valid."),
 *   settings = {
 *   }
 * )
 */
class HealthzGeocoderCheck extends HealthzCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The geocoder service.
   *
   * @var \Drupal\geocoder\GeocoderInterface
   */
  protected $geocoder;

  /**
   * The geocoder plugin manager.
   *
   * @var \Drupal\geocoder\ProviderPluginManager
   */
  protected $geocoderPluginManager;

  /**
   * Constructs a new HealthzGeocoderCheck plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\geocoder\GeocoderInterface $geocoder
   *   The geocoder service.
   * @param \Drupal\geocoder\ProviderPluginManager $geocoderPluginManager
   *   The geocoder plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, GeocoderInterface $geocoder, ProviderPluginManager $geocoderPluginManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->geocoder = $geocoder;
    $this->geocoderPluginManager = $geocoderPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('geocoder'),
      $container->get('plugin.manager.geocoder.provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'plugins' => [],
      'test_data' => '1600 Pennsylvania Avenue NW, Washington, 20500, USA',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $options = array_map(
      function ($definition) {
        return $definition['name'];
      },
      $this->geocoderPluginManager->getPlugins()
    );
    $form['test_data'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test address'),
      '#description' => $this->t('Address to use to test API endpoints.'),
      '#default_value' => $this->settings['test_data'],
    ];
    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Check plugins'),
      '#description' => $this->t('Select which plugins should be checked.'),
      '#options' => $options,
      '#default_value' => $this->settings['plugins'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    // Remove unchecked plugins and strip keys.
    $this->settings['plugins'] = array_values(array_filter($this->settings['plugins']));

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function check(): bool {
    $plugins = $this->settings['plugins'];
    $testData = $this->settings['test_data'];

    $geocoderConfig = $this->configFactory->get('geocoder.settings');
    $geocoderPluginOptionsAll = (array) $geocoderConfig->get('plugins_options');
    $errorCount = 0;
    foreach ($plugins as $pluginId) {
      $pluginOptions = $geocoderPluginOptionsAll[$pluginId] ?? [];
      $return = $this->geocoder->geocode($testData, [$pluginId], [$pluginId => $pluginOptions]);
      if (!$return instanceof AddressCollection) {
        $errorCount++;
        $this->addError($this->t('Failed to Geocode address for @plugin', [
          '@plugin' => $pluginId,
        ]));
      }
    }

    return $errorCount === 0;
  }

}
