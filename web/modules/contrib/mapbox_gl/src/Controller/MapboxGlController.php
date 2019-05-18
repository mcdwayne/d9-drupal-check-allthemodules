<?php

namespace Drupal\mapbox_gl\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of MapboxGlController.
 *
 * @author Owner
 */
class MapboxGlController extends ControllerBase {

  /**
   * Load Mapbox maps and return a render array.
   *
   * @param string $map_name
   *
   * @return array
   */
  public static function renderMap($map_name) {
    $map_info = mapbox_gl_map_get_info($map_name);

    $content['mapbox-container'] = [
      '#prefix' => '<div class="mapbox-gl-wrapper">',
      '#markup' => '<div id="'  .  $map_info['options']['container'] . '-menu" class="mapbox-gl-layer-menu"></div>'
      . '<div id="' .  $map_info['options']['container'] . '" class="mapbox-gl-container"></div>',
      '#suffix' => '</div">'
    ];

    $sources = key_exists('sources', $map_info) ? $map_info['sources']: [];
    $layers =  key_exists('layers', $map_info) ? $map_info['layers']: [];

    $settings[$map_info['options']['container']] = [
      'accessToken' => $map_info['access_token'],
      'options' => $map_info['options'],
      'sources' => $sources,
      'layers' => $layers,
      'config' => $map_info['config']
    ];

    $content['#attached']['drupalSettings']['mapboxGl'] = $settings;
    $content['#attached']['library'][] = 'mapbox_gl/libraries.mapbox-gl-js';
    $content['#attached']['library'][] = 'mapbox_gl/mapbox_gl';

    return $content;
  }

}
