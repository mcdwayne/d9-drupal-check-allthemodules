<?php

namespace Drupal\asana\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\asana\AsanaInterface;

/**
 * Provides the Asana Projects block.
 *
 * @Block(
 *   id = "asana_projects",
 *   admin_label = @Translation("Asana Projects"),
 *   category = @Translation("Asana")
 * )
 */
class AsanaProjects extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The asana service.
   *
   * @var \Drupal\asana\AsanaInterface
   */
  protected $asana;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\asana\AsanaInterface $asana
   *   The asana connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AsanaInterface $asana) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->asana = $asana;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition, $container->get('asana')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['asana_projects'] = [
      '#theme' => 'item_list',
      '#items' => $this->asana->getAllProjects(),
    ];

    return $build;
  }

}
