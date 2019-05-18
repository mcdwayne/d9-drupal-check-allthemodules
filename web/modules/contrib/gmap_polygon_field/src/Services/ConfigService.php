<?php

namespace Drupal\gmap_polygon_field\Services;

use Drupal\Core\Config\ConfigFactoryInterface;

class ConfigService {
  protected $config;

  /**
   * Constructs a ConfigService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *  The config object.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config;
  }

  /**
   * Gets a config value.
   *
   * @param string $key
   *  The config property key. 
   */
  public function get($key) {
    $value = $this->config->get('gmap_polygon_field.settings')->get($key);
    if(!$value) {
      switch ($key) {
        case 'gmap_polygon_field_stroke_color':
          return '#000000';
        case 'gmap_polygon_field_stroke_opacity':
          return 1;
        case 'gmap_polygon_field_stroke_weight':
          return 3;
        case 'gmap_polygon_field_fill_color':
          return '#000000';
      }
    }
    return $value;
  }

  /**
   * Sets a config value.
   *
   * @param string $key
   *  The config property key.
   *
   * @param string $value
   *  The config property value.
   */
  public function set($key, $value) {
    return $this->config->getEditable('gmap_polygon_field.settings')->set($key, $value);
  }
}
