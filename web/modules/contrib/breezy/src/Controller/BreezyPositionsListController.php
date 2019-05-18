<?php

namespace Drupal\breezy\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\breezy\BreezyApiManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route response for Breezy positions listing.
 */
class BreezyPositionsListController extends ControllerBase {

  /**
   * The Breezy API manager service.
   *
   * @var \Drupal\breezy\BreezyApiManager
   */
  protected $breezyApiManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('breezy.api_manager')
    );
  }

  /**
   * Creates a Breezy positions list controller.
   *
   * @param \Drupal\breezy\BreezyApiManager $breezy_api_manager
   *   The Breezy API manager service.
   */
  public function __construct(BreezyApiManager $breezy_api_manager) {
    $this->breezyApiManager = $breezy_api_manager;
  }

  /**
   * Returns Breezy positions.
   *
   * @return array
   *   A renderable array.
   */
  public function listPositions() {
    return [
      '#theme' => 'breezy_positions_listing',
      '#title' => NULL,
      '#positions' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->breezyApiManager->getPositionDetailLinks(),
      ],
    ];
  }

}
