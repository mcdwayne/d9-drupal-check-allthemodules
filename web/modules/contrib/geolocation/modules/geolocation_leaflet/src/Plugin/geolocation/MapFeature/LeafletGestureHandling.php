<?php

namespace Drupal\geolocation_leaflet\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides gesture handling.
 *
 * @MapFeature(
 *   id = "leaflet_gesture_handling",
 *   name = @Translation("Gesture Handling"),
 *   description = @Translation("Prevents map pan and zoom on page scroll. See <a target='_blank' href='https://github.com/elmarquis/Leaflet.GestureHandling'>https://github.com/elmarquis/Leaflet.GestureHandling</a>"),
 *   type = "leaflet",
 * )
 */
class LeafletGestureHandling extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings, array $context = []) {
    $render_array = parent::alterMap($render_array, $feature_settings, $context);

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
