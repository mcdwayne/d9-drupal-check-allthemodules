<?php

namespace Drupal\leaflet_maptiler_token;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield\WktGeneratorInterface;
use Drupal\leaflet\LeafletService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class for reacting to token events.
 */
class TokenOperations implements ContainerInjectionInterface {

  /**
   * The Leaflet service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Wkt Generator service.
   *
   * @var \Drupal\geofield\WktGeneratorInterface
   */
  protected $wktGenerator;

  /**
   * The Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * TokenOperations constructor.
   *
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet service.
   * @param \Drupal\geofield\WktGeneratorInterface $wkt_generator
   *   The Wkt Generator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Renderer service.
   */
  public function __construct(LeafletService $leaflet_service, WktGeneratorInterface $wkt_generator, RendererInterface $renderer) {

    $this->leafletService = $leaflet_service;
    $this->wktGenerator = $wkt_generator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('leaflet.service'),
      $container->get('geofield.wkt_generator'),
      $container->get('renderer')
    );
  }

  /**
   * Acts on token info phase.
   *
   * @return array
   *   An associative array of available tokens and token types.
   */
  public function tokenInfo() {
    /*
     * Sets the token group type.
     */
    $type = [
      'name' => t('Maptiler Token'),
      'description' => t('Token for showing a map based on Maptiler API.'),
    ];
    /*
     * Sets the latitude, longitude, zoom and height token.
     */
    $tokens['lat_lng_zoom_height'] = [
      'name' => t('Maptiler latitude, longitude, zoom and height token'),
      'description' => t('Maptiler token that renders a map based on latitude, longitude, zoom and height arguments.'),
    ];

    return [
      'types' => ['maptiler' => $type],
      'tokens' => ['maptiler' => $tokens],
    ];
  }

  /**
   * Acts on tokens phase.
   *
   * @param string $type
   *   The machine-readable name of the type (group) of token being replaced.
   * @param array $tokens
   *   An array of tokens to be replaced.
   * @param array $data
   *   An associative array of data objects to be used when
   *   generating replacement values.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   An associative array of replacement value.
   *
   * @throws \Exception
   */
  public function tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];

    if ($type == 'maptiler') {
      /*
       * Process each maptiler token.
       */
      foreach ($tokens as $name => $original) {
        /*
         * Extracts token name.
         */
        $exploded_name = explode(':', $name);
        /*
         * If the token has arguments.
         */
        if (count($exploded_name) > 1) {
          switch ($exploded_name[0]) {
            case 'lat_lng_zoom_height':
              $exploded_coord = explode('+', $exploded_name[1]);

              if (count($exploded_coord) > 1) {
                /*
                 * Generate Point based on longitude and latitude.
                 */
                $coord = $this->wktGenerator->wktGeneratePoint([$exploded_coord[1], $exploded_coord[0]]);
                /*
                 * Process coordinates.
                 */
                $points = $this->leafletService->leafletProcessGeofield($coord);
                /*
                 * Gets the Maptiler map info.
                 */
                $map = $this->leafletService->leafletMapGetInfo('Maptiler');
                /*
                 * If Maptiler map exists.
                 */
                if (!empty($map)) {
                  /*
                   * Adds the zoom settings based on token arguments.
                   */
                  if (!empty($exploded_coord[2]) && ctype_digit($exploded_coord[2])) {
                    $map["settings"]["zoomDefault"] = (int) $exploded_coord[2];
                  }
                  if (!empty($exploded_coord[3]) && ctype_digit($exploded_coord[3])) {
                    /*
                     * Renders the map as a replacement with custom height.
                     */
                    $replacements[$original] = $this->renderer->render($this->leafletService->leafletRenderMap($map, [$points[0]], "{$exploded_coord[3]}px"));
                  }
                  else {
                    /*
                     * Renders the map as a replacement with default height.
                     */
                    $replacements[$original] = $this->renderer->render($this->leafletService->leafletRenderMap($map, [$points[0]]));
                  }
                }
              }
              break;
          }
        }
      }
    }
    return $replacements;
  }

}
