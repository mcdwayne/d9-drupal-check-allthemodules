<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides marker clusterer.
 *
 * @MapFeature(
 *   id = "leaflet_marker_clusterer",
 *   name = @Translation("Marker Clusterer"),
 *   description = @Translation("Cluster close markers together."),
 *   type = "leaflet",
 * )
 */
class LeafletMarkerClusterer extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    $default_settings = parent::getDefaultSettings();

    $default_settings['cluster_settings'] = [];

    return $default_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array $settings, array $parents) {
    $settings = $this->getSettings($settings);

    $options = [
      'show_coverage_on_hover' => $this->t('When you mouse over a cluster it shows the bounds of its markers.'),
      'zoom_to_bounds_on_click' => $this->t('When you click a cluster we zoom to its bounds.'),
    ];

    $form['cluster_settings'] = [
      '#type' => 'checkboxes',
      '#options' => $options,
      '#title' => $this->t('Marker Cluster default settings'),
      '#default_value' => $settings['cluster_settings'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings, array $context = []) {
    $render_array = parent::alterMap($render_array, $feature_settings, $context);
    $cluster_settings = NULL;
    if (isset($feature_settings['cluster_settings'])) {
      $cluster_settings = $feature_settings['cluster_settings'];
    }
    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      empty($render_array['#attached']) ? [] : $render_array['#attached'],
      [
        'library' => [
          'geolocation_leaflet/mapfeature.' . $this->getPluginId(),
        ],
        'drupalSettings' => [
          'geolocation' => [
            'maps' => [
              $render_array['#id'] => [
                $this->getPluginId() => [
                  'enable' => TRUE,
                  'showCoverageOnHover' => is_string($cluster_settings['show_coverage_on_hover']) ? TRUE : FALSE,
                  'zoomToBoundsOnClick' => is_string($cluster_settings['zoom_to_bounds_on_click']) ? TRUE : FALSE,
                ],
              ],
            ],
          ],
        ],
      ]
    );

    return $render_array;
  }

}
