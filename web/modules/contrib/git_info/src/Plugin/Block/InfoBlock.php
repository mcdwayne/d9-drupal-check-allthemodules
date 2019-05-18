<?php

namespace Drupal\git_info\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\git_info\GitInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'InfoBlock' block.
 *
 * @Block(
 *  id = "info_block",
 *  admin_label = @Translation("Info block"),
 * )
 */
class InfoBlock extends BlockBase implements ContainerFactoryPluginInterface {


  protected $gitInfo;

  /**
   * Constructs an InfoBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GitInfo $git_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->gitInfo = $git_info;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('git_info.git_info')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $hash = $this->gitInfo->getApplicationVersionString();
    $build['info_block']['#markup'] = $hash;
    $build['#cache'] = [
      // @todo: Make a cache tag and clear it manually.
      'max-age' => 0,
    ];

    return $build;
  }

}
