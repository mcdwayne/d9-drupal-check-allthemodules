<?php

namespace Drupal\route_planner\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides the RouteTarget block.
 *
 * @Block(
 *   id = "route_target",
 *   admin_label = @Translation("Route Planner Address Field")
 * )
 */
class RouteTarget extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $build = array();

    // Add google maps api.
    $build['#attached']['library'][] = 'route_planner/googleapis';

    // Add some custom javascript to display the map.
    $build['#attached']['library'][] = 'route_planner/route_planner';

    // Attach the settings from route_planner settings form.
    $route_settings = array(
      'zoomlevel'          => \Drupal::config('route_planner.settings')->get('route_planner_map_zoom'),
      'zoomcontrol'        => \Drupal::config('route_planner.settings')->get('route_planner_map_zoomcontrol'),
      'scrollwheel'        => \Drupal::config('route_planner.settings')->get('route_planner_map_scrollwheel'),
      'mapTypeControl'     => \Drupal::config('route_planner.settings')->get('route_planner_map_maptypecontrol'),
      'scaleControl'       => \Drupal::config('route_planner.settings')->get('route_planner_map_scalecontrol'),
      'draggable'          => \Drupal::config('route_planner.settings')->get('route_planner_map_draggable'),
      'doubbleclick'       => \Drupal::config('route_planner.settings')->get('route_planner_map_doubbleclick'),
      'streetviewcontrol'  => \Drupal::config('route_planner.settings')->get('route_planner_map_streetviewcontrol'),
      'overviewmapcontrol' => \Drupal::config('route_planner.settings')->get('route_planner_map_overviewmapcontrol'),
      'unitSystem'         => \Drupal::config('route_planner.settings')->get('route_planner_unitsystem'),
      'defaultui'          => \Drupal::config('route_planner.settings')->get('route_planner_map_defaultui'),
      'end'                => \Drupal::config('route_planner.settings')->get('route_planner_address'),

    );

    $build['#attached']['drupalSettings']['route_planner'] = $route_settings;

    // Get the address form.
    $extra = '';
    $build['address_form'] = \Drupal::formBuilder()->getForm('Drupal\route_planner\Form\RoutePlannerAddressForm', $extra);
    return $build;
  }
}
