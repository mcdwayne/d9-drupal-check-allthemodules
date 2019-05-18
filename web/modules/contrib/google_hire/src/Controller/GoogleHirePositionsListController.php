<?php

namespace Drupal\google_hire\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_hire\GoogleHireApiManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route response for Google Hire positions listing.
 */
class GoogleHirePositionsListController extends ControllerBase {

  /**
   * The Google Hire API manager service.
   *
   * @var \Drupal\google_hire\GoogleHireApiManager
   */
  protected $googleHireApiManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_hire.api_manager')
    );
  }

  /**
   * Creates a Google Hire positions list controller.
   *
   * @param \Drupal\google_hire\GoogleHireApiManager $google_hire_api_manager
   *   The Google Hire API manager service.
   */
  public function __construct(GoogleHireApiManager $google_hire_api_manager) {
    $this->googleHireApiManager = $google_hire_api_manager;
  }

  /**
   * Returns Google Hire positions.
   *
   * @return array
   *   A renderable array.
   */
  public function listPositions() {
    return [
      '#theme' => 'google_hire_positions_listing',
      '#title' => NULL,
      '#positions' => [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => $this->googleHireApiManager->getPositionDetailLinks(),
      ],
    ];
  }

}
