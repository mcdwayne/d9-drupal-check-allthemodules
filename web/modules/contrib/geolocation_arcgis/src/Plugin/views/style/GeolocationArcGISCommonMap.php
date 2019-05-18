<?php

namespace Drupal\geolocation_arcgis\Plugin\views\style;

use Drupal\geolocation\Plugin\views\style\CommonMapBase;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Allow to display several field items on a common map.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "arcgis_maps_common",
 *   title = @Translation("Geolocation ArcGIS Maps API - CommonMap"),
 *   help = @Translation("Display geolocations on a common map."),
 *   theme = "views_view_list",
 *   display_types = {"normal"},
 * )
 */
class GeolocationArcGISCommonMap extends CommonMapBase {

    protected $mapProviderId = 'arcgis_maps';
    protected $mapProviderSettingsFormId = 'arcgis_maps_settings';

    /**
     * {@inheritdoc}
     */
    public function render() {
        $build = parent::render();
        $build['#maptype'] = 'arcgis_maps';
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
        $build['#map_settings']['map_settings'] = $custom_settings;
        $build['#attached'] = BubbleableMetadata::mergeAttachments(
            empty($build['#attached']) ? [] : $build['#attached'],
            [
                'library' => [
                    'geolocation_arcgis/arcgis_maps.api',
                ],
            ]
        );
        return $build;
    }
}
