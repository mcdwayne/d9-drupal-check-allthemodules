<?php

namespace Drupal\leaflet_maptiler;

use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\leaflet\LeafletService;

/**
 * Class MaptilerLeafletServiceDecorator.
 *
 * @package Drupal\leaflet_maptiler
 */
class MaptilerLeafletServiceDecorator extends LeafletService {

  /**
   * The Leaflet inner service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Language Manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * MaptilerLeafletServiceDecorator constructor.
   *
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet inner service.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(LeafletService $leaflet_service, GeoPHPInterface $geophp_wrapper, ModuleHandlerInterface $module_handler, LinkGeneratorInterface $link_generator, LanguageManagerInterface $language_manager) {
    parent::__construct($geophp_wrapper, $module_handler, $link_generator);
    $this->leafletService = $leaflet_service;
    $this->languageManager = $language_manager;
  }

  /**
   * Load all Leaflet required client files and return markup for a map.
   *
   * @param array $map
   *   The map settings array.
   * @param array $features
   *   The features array.
   * @param string $height
   *   The height value string.
   *
   * @return array
   *   The leaflet_map render array.
   */
  public function leafletRenderMap(array $map, array $features = [], $height = '400px') {
    /*
     * Gets the build renderable array from the inner service.
     */
    $build = $this->leafletService->leafletRenderMap($map, $features, $height);
    /*
     * Attach leaflet_maptiler library.
     */
    $build["#attached"]["library"][] = 'leaflet_maptiler/leaflet_maptiler';
    $build["#attached"]["drupalSettings"]["leaflet_maptiler"] = [
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
    ];
    return $build;
  }

  /**
   * Magic method to return any method call inside the inner service.
   */
  public function __call($method, $args) {
    return call_user_func_array(array($this->leafletService, $method), $args);
  }

}
