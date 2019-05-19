<?php

namespace Drupal\vsauce_sticky_popup\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class VsauceStickyPopupController.
 *
 * @package Drupal\vsauce_sticky_popup\Controller
 *   The Main controller.
 */
class VsauceStickyPopupController extends ControllerBase {

  protected $state;
  protected $configFactory;
  protected $pathId;

  /**
   * VsauceStickyPopupController constructor.
   *
   * {@inheritdoc}
   */
  public function __construct(ResettableStackedRouteMatchInterface $routeMatch, StateInterface $state, ConfigFactoryInterface $configFactory) {
    $this->pathId = $routeMatch->getRouteName();
    $this->state = $state;
    $this->configFactory = $configFactory;
  }

  /**
   * Get config by default anc pathid.
   *
   * @return array
   *   Return array with all config.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConfig() {
    $config = $this->configFactory->get('vsauce_sticky_popup.default_config');
    $this->findConfigByCurrentPathId();
    return [
      'current_path_id' => $this->pathId,
      'default' => [
        'position_sticky_popup' => $config->get('position_sticky_popup'),
        'position_open_button' => $config->get('position_open_button'),
        'position_arrow' => $config->get('position_arrow'),
      ],
      'vsp_config' => $this->findConfigByCurrentPathId(),
    ];
  }

  /**
   * Find config by current path id.
   *
   * @return array
   *   Array empty or array of items.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function findConfigByCurrentPathId() {

    // Get current route name.
    $pathId = $this->pathId;
    $query = \Drupal::entityQuery('v_sticky_config_entity');

    $query->condition('path_id', $pathId, '=');
    $entity_ids = $query->execute();
    $id = reset($entity_ids);

    if ($id) {
      $entityManager = \Drupal::entityManager()->getStorage('v_sticky_config_entity');
      $entity = $entityManager->load($id);
      return [
        'path_id' => $entity->get('path_id'),
        'position_sticky_popup' => $entity->get('position_sticky_popup'),
        'position_open_button' => $entity->get('position_open_button'),
        'collapsed' => $entity->get('collapsed'),
        'position_arrow' => $entity->get('position_arrow'),
        'content_type' => $entity->get('content_type'),
        'content' => $entity->get('content'),
      ];
    }
    return [];
  }

}
