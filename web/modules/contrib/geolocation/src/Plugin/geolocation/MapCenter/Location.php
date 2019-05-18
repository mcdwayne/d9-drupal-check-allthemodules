<?php

namespace Drupal\geolocation\Plugin\geolocation\MapCenter;

use Drupal\geolocation\LocationManager;
use Drupal\geolocation\MapCenterInterface;
use Drupal\geolocation\MapCenterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Location based map center.
 *
 * @MapCenter(
 *   id = "location_plugins",
 *   name = @Translation("Location Plugins"),
 *   description = @Translation("Select a location plugin."),
 * )
 */
class Location extends MapCenterBase implements MapCenterInterface {

  /**
   * Location manager.
   *
   * @var \Drupal\geolocation\LocationManager
   */
  protected $locationManager;

  protected $locationPluginId = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocationManager $location_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->locationManager = $location_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.geolocation.location')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm($option_id = NULL, array $settings = [], $context = NULL) {
    if (!$this->locationManager->hasDefinition($option_id)) {
      return [];
    }

    /** @var \Drupal\geolocation\LocationInterface $location_plugin */
    $location_plugin = $this->locationManager->createInstance($option_id);
    $form = $location_plugin->getSettingsForm($location_plugin->getPluginId(), $settings, $context);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableMapCenterOptions($context = NULL) {
    $options = [];

    /** @var \Drupal\geolocation\LocationInterface $location_plugin */
    foreach ($this->locationManager->getDefinitions() as $location_plugin_id => $location_plugin_definition) {
      /** @var \Drupal\geolocation\LocationInterface $location_plugin */
      $location_plugin = $this->locationManager->createInstance($location_plugin_id);
      foreach ($location_plugin->getAvailableLocationOptions($context) as $location_id => $location_label) {
        $options[$location_id] = $location_label;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $map, $center_option_id, array $center_option_settings, $context = NULL) {
    if (!$this->locationManager->hasDefinition($center_option_id)) {
      return $map;
    }

    /** @var \Drupal\geolocation\LocationInterface $location */
    $location = $this->locationManager->createInstance($center_option_id);

    $map_center = $location->getCoordinates($center_option_id, $center_option_settings, $context);
    if (empty($map_center)) {
      return FALSE;
    }

    $map['#centre'] = $map_center;

    return $map;
  }

}
