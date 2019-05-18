<?php

namespace Drupal\geolocation_arcgis\Plugin\geolocation\MapProvider;

use Drupal\Core\Url;
use Drupal\geolocation\MapProviderBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Component\Utility\Html;

/**
 * Provides Google Maps.
 *
 * @MapProvider(
 *   id = "arcgis_maps",
 *   name = @Translation("ArcGIS Maps"),
 *   description = @Translation("You do require an API key for this plugin to work."),
 * )
 */
class ArcGISMap extends MapProviderBase {
    /**
     * {@inheritdoc}
     */
    public function getSettings(array $settings) {
        $settings = parent::getSettings($settings);
        $service = \Drupal::service('geolocation_arcgis.manager');
        $home = $service->getHomeLocation();
        $custom_settings = [
            'geocodeProxyUrl' => $service->getGeocodeApiUrl(),
            'token' => $service->getApiToken(),
            'map_type' => $service->getBaseMapType()
        ];
        if (!empty($home)) {
            $custom_settings['home_location'] = [
                $home['lng'],
                $home['lat']
            ];
        }
        return array_merge($settings, $custom_settings);
    }

    /**
     * {@inheritdoc}
     */
    public function alterRenderArray(array $render_array, array $map_settings, array $context = []) {
        $map_settings = $this->getSettings($map_settings);
        if (isset($render_array['#settings'])) {
            $map_settings = array_merge($map_settings, $render_array['#settings']);
        }
        $library = ['geolocation_arcgis/arcgis_maps.api'];
        if (isset($render_array['#library'])) {
            $library = $render_array['#library'];
        }
        $render_array['#attached'] = BubbleableMetadata::mergeAttachments(
            empty($render_array['#attached']) ? [] : $render_array['#attached'],
            [
                'library' => $library,
                'drupalSettings' => [
                    'geolocation' => [
                        'maps' => [
                            $render_array['#id'] => [
                                'settings' => [
                                    'map_settings' => $map_settings,
                                    'map_container_id' => Html::getUniqueId('container_' . $render_array['#id'])
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $render_array = parent::alterRenderArray($render_array, $map_settings, $context);
        return $render_array;
    }
}
