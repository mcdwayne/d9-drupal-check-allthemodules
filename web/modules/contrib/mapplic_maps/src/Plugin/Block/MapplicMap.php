<?php
/**
 * @file
 * Contains \Drupal\mapplic\Plugin\Block\MapplicMap.
 */
namespace Drupal\mapplic\Plugin\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
/**
 * Provides a 'article' block.
 *
 * @Block(
 *   id = "mapplic_map_block",
 *   admin_label = @Translation("Mapplic Map block"),
 *   category = @Translation("Mapplic world map block")
 * )
 */
class MapplicMap extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('mapplic.settings');
  	$mapplicSettings = [
  		'mapplic' => [
  			'source' => Url::fromRoute("mapplic.json"),
        'sidebar' => $config->get('mapplic_sidebar'),
        'mapfill' => $config->get('mapplic_mapfill'),
        'zoombuttons' => $config->get('mapplic_zoombuttons'),
        'clearbutton' => true,
        'minimap' => true,
        'locations' => $config->get('mapplic_locations'),
        'fullscreen' => $config->get('mapplic_fullscreen'),
        'hovertip' => $config->get('mapplic_hovertip'),
        'search' => $config->get('mapplic_search'),
        'animate' => $config->get('mapplic_animate'),
        'developer' => $config->get('mapplic_developer_mode'),
        'zoom' => $config->get('mapplic_zoom'),
        'maxscale' => $config->get('mapplic_max_scale'),
  		],
  	];
    return [
      '#type' => 'markup',
      '#markup' => '<div id="mapplic"></div>',
      '#attached' => [
      	'library' => [
      		'mapplic/mapplic-map',
      	],
      	'drupalSettings' => $mapplicSettings,
      ],
    ];
  }
}