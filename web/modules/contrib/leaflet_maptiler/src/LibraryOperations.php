<?php

namespace Drupal\leaflet_maptiler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to library events.
 */
class LibraryOperations implements ContainerInjectionInterface {

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
   * Acts on library info alter phase.
   *
   * @param array $libraries
   *   An associative array of libraries registered by $extension.
   * @param string $extension
   *   Can either be 'core' or the machine name of the extension
   *   that registered the libraries.
   */
  public function libraryInfoAlter(array &$libraries, $extension) {
    if ($extension == 'leaflet_maptiler') {
      foreach ($this->getMaptilerExternalLibraries() as $key => $value) {
        if (!empty($value)) {

          switch ($key) {
            case 'js':
              $libraries["leaflet_maptiler"][$key][$value] = [
                'type' => 'external',
              ];
              break;
            case 'css':
              if (!empty($libraries["leaflet_maptiler"][$key]['component'])) {
                $libraries["leaflet_maptiler"][$key]['component'][$value] = [
                  'type' => 'external',
                ];
              }
              else {
                $libraries["leaflet_maptiler"][$key]['component'] = [
                  $value => [
                    'type' => 'external',
                  ],
                ];
              }
              break;
          }
        }
      }
    }
  }

  /**
   * Gets the Maptiler external libraries defined in the configurations.
   *
   * @return array
   *   An array containing the Maptiler external libraries.
   */
  private function getMaptilerExternalLibraries() {
    /*
     * Loads the leaflet_maptiler configurations.
     */
    $config = $this->configFactory->get('leaflet_maptiler.settings');
    /*
     * Initialize libraries.
     */
    $libraries = [];
    /*
     * Add geocoder js library.
     */
    if (!empty($config->get('leaflet_maptiler_geocoder_js'))) {
      $libraries['js'] = $config->get('leaflet_maptiler_geocoder_js');
    }
    /*
     * Add geocoder css library.
     */
    if (!empty($config->get('leaflet_maptiler_geocoder_css'))) {
      $libraries['css'] = $config->get('leaflet_maptiler_geocoder_css');
    }
    return $libraries;
  }

}
