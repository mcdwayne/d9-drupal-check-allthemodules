<?php

namespace Drupal\googlemap_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GoogleMap Block' block.
 *
 * @Block(
 *   id = "googlemap_block",
 *   admin_label = @Translation("Google Map Block"),
 * )
 */
class GoogleMapBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  private $database;

  /**
   * {@inheritdoc}
   *
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->database = Database::getConnection('default');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('config.factory')
    );
  }

  /**
   * Create a 'GoogleMap Block' block.
   */
  public function build() {
    $query = $this->database->select('google_map_location_list', 'u');
    $query->fields('u');
    $location_data = $query->execute()->fetchAll();
    $mapData = [];
    foreach ($location_data as $data) {
      $mapData[] = [
        'location_name' => $data->location_name,
        'location_address' => $data->address,
        'lat' => $data->latitude,
        'long' => $data->longitude,
      ];
    }
    // Get data form Google Map config setting.
    $config = $this->configFactory->get('googlemap_block.settings');
    $api_key = $config->get('api_key');
    $map_height = $config->get('map_height');
    $map_width = $config->get('map_width');
    $map_zoom_level = $config->get('map_zoom_level');
    $lat = $config->get('lat');
    $long = $config->get('long');

    $output = [
      '#theme' => 'googlemap_block',
      '#map_height' => $map_height,
      '#map_width' => $map_width,
      '#attached' => [
        'library' => [
          'googlemap_block/googlemap.admin',
        ],
        'drupalSettings' => [
          'api_key' => $api_key,
          'all_address' => $mapData,
          'map_zoom_level' => $map_zoom_level,
          'lat' => $lat,
          'long' => $long,
        ],
      ],
      '#cache' => ['max-age' => 0],
    ];
    return $output;
  }

}
