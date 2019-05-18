<?php

namespace Drupal\leaflet_maptiler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to Leaflet events.
 */
class LeafletOperations implements ContainerInjectionInterface {

  /**
   * The Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * LeafletOperations constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
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
   * Loads Leaflet default Maptiler map.
   *
   * @return array
   *   An array containing the default map for the module.
   */
  public function leafletMapInfo() {
    /*
     * Initialize maps array.
     */
    $maps = [];
    /*
     * Loads maptiler settings.
     */
    $maptiler_settings = $this->getMaptilerSettings();
    /*
     * Initialize leaflet map for Maptiler.
     */
    $maps['Maptiler'] = [
      'label' => t('Maptiler'),
      'description' => t('Maptiler map.'),
      'settings' => [
        'zoomDefault' => 10,
        'minZoom' => 0,
        'maxZoom' => 18,
        'dragging' => TRUE,
        'touchZoom' => TRUE,
        'scrollWheelZoom' => TRUE,
        'doubleClickZoom' => TRUE,
        'zoomControl' => TRUE,
        'attributionControl' => TRUE,
        'trackResize' => TRUE,
        'fadeAnimation' => TRUE,
        'zoomAnimation' => TRUE,
        'closePopupOnClick' => TRUE,
        'layerControl' => TRUE,
      ],
      'layers' => [],
    ];
    /*
     * Split layers into an array.
     */
    $maptiler_layers = explode(',', $maptiler_settings['leaflet_maptiler_layers']);
    /*
     * If there are layers set for Maptiler.
     */
    if (!empty($maptiler_layers) && !empty($maptiler_settings['leaflet_maptiler_api_key'])) {
      /*
       * Sets up each layer into the leaflet map settings.
       */
      foreach ($maptiler_layers as $key => $layer) {
        /*
         * Gets the style and removes spaces from the string.
         */
        $style = str_replace(' ', '', $layer);
        /*
         * Adds the layer to the map.
         */
        $maps['Maptiler']['layers'][$style] = [
          'urlTemplate' => "//maps.tilehosting.com/styles/{$style}/{z}/{x}/{y}.png?key={$maptiler_settings['leaflet_maptiler_api_key']}",
          'options' => [
            'attribution' => '<a href="https://www.maptiler.com/license/maps/" target="_blank">© MapTiler</a> <a href="https://www.openstreetmap.org/copyright" target="_blank">© OpenStreetMap contributors</a>',
          ],
          'layer_type' => 'overlay',
          'layer_hidden' => TRUE,
        ];
        /*
         * Set the first layer as visible and as a base layer.
         */
        if ($key === 0) {
          $maps['Maptiler']['layers'][$style]['layer_hidden'] = FALSE;
          $maps['Maptiler']['layers'][$style]['layer_type'] = 'base';
        }
      }
    }
    return $maps;
  }

  /**
   * Gets the Maptiler settings from Config Factory.
   *
   * @return array
   *   An array containing the Maptiler settings.
   */
  private function getMaptilerSettings() {
    $config = $this->configFactory->get('leaflet_maptiler.settings');
    return [
      'leaflet_maptiler_api_key' => $config->get('leaflet_maptiler_api_key'),
      'leaflet_maptiler_layers' => $config->get('leaflet_maptiler_layers'),
    ];
  }

}
