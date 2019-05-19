<?php

namespace Drupal\geolocation_google_maps\Plugin\geolocation\MapFeature;

use Drupal\geolocation\MapFeatureBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Provides marker infowindow.
 *
 * @MapFeature(
 *   id = "map_disable_poi",
 *   name = @Translation("Disable POIs"),
 *   description = @Translation("Disable points of interest feature. Attention: May interfere with MapStyle."),
 *   type = "google_maps",
 * )
 */
class MapDisablePOI extends MapFeatureBase {

  /**
   * {@inheritdoc}
   */
  public function alterMap(array $render_array, array $feature_settings, array $context = []) {
    $render_array = parent::alterMap($render_array, $feature_settings, $context);

    $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
      empty($render_array['#attached']) ? [] : $render_array['#attached'],
      [
        'library' => [
          'geolocation_google_maps/mapfeature.' . $this->getPluginId(),
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
