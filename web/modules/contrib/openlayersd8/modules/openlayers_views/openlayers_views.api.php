<?php
/**
 * @file
 * Hook documentation for openlayers_views module.
 */

/**
 * Adjust the array representing a openlayers feature/marker.
 *
 * @param array $feature
 *   The openlayers feature. Available keys are:
 *   - type: Indicates the type of feature (usually one of these: point,
 *     polygon, linestring, multipolygon, multipolyline).
 *   - popup: This value is displayed in a popup after the user clicks on the
 *     feature.
 *   - label: Not used at the moment.
 *   - Other possible keys include "lat", "lon", "points", "component",
 *     depending on feature type. {@see openlayers_process_geofield()} for details.
 * @param \Drupal\views\ResultRow $row
 *   The views result row.
 * @param \Drupal\openlayers_views\Plugin\views\row\OpenLayersMarker $rowPlugin
 *   The row plugin used for rendering the feature.
 */
function hook_openlayers_views_feature_alter(array &$feature, \Drupal\views\ResultRow $row, \Drupal\openlayers_views\Plugin\views\row\OpenLayersMarker $rowPlugin) {
}

/**
 * Adjust the array representing a openlayers feature group.
 *
 * @param array $group
 *   The openlayers feature group. Available keys are:
 *   - group: Indicates whether the contained features should be rendered as a
 *     layer group. Set to FALSE to render contained features ungrouped.
 *   - features: List of features contained in this group.
 *   - label: The group label, e.g. used for the layer control widget.
 * @param \Drupal\openlayers_views\Plugin\views\style\MarkerDefault $stylePlugin
 *   The style plugin used for rendering the feature group.
 */
function hook_openlayers_views_feature_group_alter(array &$group, \Drupal\openlayers_views\Plugin\views\style\MarkerDefault $stylePlugin) {
}
