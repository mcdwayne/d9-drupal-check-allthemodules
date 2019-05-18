<?php

namespace Drupal\breezy\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\breezy\BreezyApiManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Breezy positions list block.
 *
 * @Block(
 *   id = "breezy_positions_list_block",
 *   admin_label = @Translation("Breezy: Positions Listing"),
 * )
 */
class BreezyPositionsListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Breezy API manager service.
   *
   * @var \Drupal\breezy\BreezyApiManager
   */
  protected $breezyApiManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('breezy.api_manager')
    );
  }

  /**
   * Creates a Breezy positions list controller.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\breezy\BreezyApiManager $breezy_api_manager
   *   The Breezy API manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BreezyApiManager $breezy_api_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->breezyApiManager = $breezy_api_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'breezy_positions_listing',
      '#title' => $this->t('Positions'),
      '#positions' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->breezyApiManager->getPositionDetailLinks(),
      ],
    ];
  }

}
