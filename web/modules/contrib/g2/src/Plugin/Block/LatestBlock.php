<?php

/**
 * @file
 * Contains the Latest(n) block plugin.
 */

namespace Drupal\g2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\g2\Latest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LatestBlock is the Latest(n) block plugin.
 *
 * @Block(
 *   id = "g2_latest",
 *   admin_label = @Translation("G2 Latest(n)"),
 *   category = @Translation("G2")
 * )
 */
class LatestBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The g2.settings.block.latest configuration.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * The g2.latest service.
   *
   * @var \Drupal\g2\Latest
   */
  protected $latest;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block definition.
   * @param \Drupal\g2\Latest $latest
   *   The g2.latest service.
   * @param array $block_config
   *   The block configuration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
    Latest $latest, array $block_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->latest = $latest;
    $this->blockConfig = $block_config;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $count = $this->blockConfig['count'];
    $links = $this->latest->getLinks($count);

    $result = [
      '#theme' => 'item_list',
      '#items' => $links,
    ];
    return $result;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    /* @var \Drupal\g2\Latest $latest */
    $latest = $container->get('g2.latest');

    /* @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $container->get('config.factory');

    /* @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $config_factory->get('g2.settings');

    $block_config = $config->get('block.latest');

    return new static($configuration, $plugin_id, $plugin_definition,
      $latest, $block_config);
  }

}
