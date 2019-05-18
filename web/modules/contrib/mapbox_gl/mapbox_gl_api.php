<?php

/**
 * @file
 * API documentation.
 */

/**
 * Define one or more map definitions to be used when rendering a map.
 * mapbox_gl_get_info() will load each map and the returned
 * array is then passed to mapbox_gl_render_map() along with sources, layers
 * and options.
 *
 * The options array maps to the options available to Mapbox GL map object,
 * see https://www.mapbox.com/mapbox-gl-js/api/
 *
 * @return array
 */
function hook_mapbox_gl_map_info() {
  // For options, see: https://www.mapbox.com/mapbox-gl-js/api/
  return [
    'Streets' =>
    [
      'access_token' => 'pk.zzzzIjoiZGlnaXRhbGlua3Rhcxxxxxx1234iYSI6ImNqMTVsbmFmazAxZHoycW83M3prY2Fhb3UifQ._eVTUEEMHeaQ3QY0gQYmYw',
      'options' => [
        'container' => 'mapbox-streets',
        'style' => 'mapbox://styles/mapbox/streets-v9',
        'zoom' => 6,
        'center' => [146.315918, -41.640079],
      ],
      'config' =>
      [
        'height' => '400px',
        'popup' => 'popup',
        'controls' =>
        [
          'NavigationControl' => 'top-left',
          'AttributionControl' => [
            'compact' => true
          ],
          'MapboxGeocoder' => [
            'accessToken' => 'pk.zzzzIjoiZGlnaXRhbGlua3Rhcxxxxxx1234iYSI6ImNqMTVsbmFmazAxZHoycW83M3prY2Fhb3UifQ._eVTUEEMHeaQ3QY0gQYmYw'
          ]
        ]
      ],
      'layers' =>
      [
        [
          'id' => 'Ports',
          'type' => 'circle',
          'source' => [
            'type' => 'geojson',
            'data' => 'https://d2ad6b4ur7yvpq.cloudfront.net/naturalearth-3.3.0/ne_10m_ports.geojson'
          ]
        ],
        [
          'id' => 'Terrain',
          'type' => 'line',
          'source' =>
          [
            'type' => 'vector',
            'url' => 'mapbox://mapbox.mapbox-terrain-v2',
          ],
          'source-layer' => 'contour',
          'layout' => [
            'line-join' => 'round',
            'line-cap' => 'round',
          ],
          'paint' => [
            'line-color' => '#ff69b4',
            'line-width' => 1
          ]
        ],
        [
          'id' => 'My Locations',
          'type' => 'circle',
          'source' => [
            'type' => 'vector',
            'url' => 'mapbox://companyname.cj9s1qkic03un32plq1klqfms-5c4qn'
          ],
          'source-layer' => 'My_Locations_Tileset'
        ]
      ]
    ]
  ];
}

/**
 * Alters the map definitions for one or more maps that were defined by
 * hook_mapbox_map_info().
 *
 * See:  https://www.mapbox.com/mapbox-gl-js/api/
 *
 * @param array $map_info
 */
function hook_mapbox_gl_map_info_alter(array &$map_info) {
  // Change the label.
  $map_info['Streets']['label'] = "My New Streets Page";
}
