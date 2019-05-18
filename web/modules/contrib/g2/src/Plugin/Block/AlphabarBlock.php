<?php

/**
 * @file
 * Contains the Alphabar block plugin.
 */

namespace Drupal\g2\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\g2\Alphabar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AlphabarBlock is the Alphabar block plugin.
 *
 * @Block(
 *   id = "g2_alphabar",
 *   admin_label = @Translation("G2 Alphabar"),
 *   category = @Translation("G2")
 * )
 */
class AlphabarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Alphabar service.
   *
   * @var \Drupal\g2\Alphabar
   */
  protected $alphabar;

  /**
   * The g2.settings.block.alphabar configuration.
   *
   * @var array
   */
  protected $blockConfig;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The block ID.
   * @param mixed $plugin_definition
   *   The block definition.
   * @param \Drupal\g2\Alphabar $alphabar
   *   The Alphabar service.
   * @param array $block_config
   *   The block configuration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
    Alphabar $alphabar, array $block_config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->alphabar = $alphabar;
    $this->blockConfig = $block_config;
  }

  /**
   * Builds and returns the renderable array for this block plugin.
   *
   * If a block should not be rendered because it has no content, then this
   * method must also ensure to return no content: it must then only return an
   * empty array, or an empty array with #cache set (with cacheability metadata
   * indicating the circumstances for it being empty).
   *
   * @return array
   *   A renderable array representing the content of the block.
   *
   * @see \Drupal\block\BlockViewBuilder
   */
  public function build() {
    $links = $this->alphabar->getLinks();
    $result = [
      '#theme' => 'g2_alphabar',
      '#alphabar' => $links,
      '#row_length' => $this->blockConfig['row_length'],
      '#attached' => [
        'library' => ['g2/g2-alphabar'],
      ]
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
    /* @var \Drupal\g2\Alphabar $alphabar */
    $alphabar = $container->get('g2.alphabar');

    /* @var \Drupal\Core\Config\ConfigFactory $config_factory */
    $config_factory = $container->get('config.factory');

    /* @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $config_factory->get('g2.settings');

    $block_config = $config->get('block.alphabar');

    return new static($configuration, $plugin_id, $plugin_definition,
      $alphabar, $block_config);
  }

}
