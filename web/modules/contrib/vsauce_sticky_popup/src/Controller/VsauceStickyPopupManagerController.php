<?php

namespace Drupal\vsauce_sticky_popup\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\vsauce_sticky_popup\VsauceStickyPopupSingleton;

/**
 * Class VsauceStickyPopupManagerController.
 *
 * @package Drupal\vsauce_sticky_popup\Controller
 */
class VsauceStickyPopupManagerController extends ControllerBase implements ContainerInjectionInterface, VsauceStickyPopupControllerInterface {
  private $state;

  private $vspcSingleton;

  private $pathId;
  private $instance;

  private $defaultConfig;

  private $renderer;

  /**
   * The Constructor.
   *
   * {@inheritdoc}.
   */
  public function __construct(ResettableStackedRouteMatchInterface $routeMatch, StateInterface $state, ConfigFactoryInterface $configFactory, Renderer $renderer) {

    $this->state = $state;
    $this->vspcSingleton = VsauceStickyPopupSingleton::getInstance();
    $this->pathId = $routeMatch->getRouteName();

    // Define instance structure.
    $config = $configFactory->get('vsauce_sticky_popup.default_config');
    $this->defaultConfig = [
      'position_sticky_popup' => $config->get('position_sticky_popup'),
      'position_open_button' => $config->get('position_open_button'),
      'position_arrow' => $config->get('position_arrow'),
    ];

    $this->addItemsByPathId();
    $this->renderer = $renderer;
  }

  /**
   * Get instance of Class.
   *
   * @return mixed
   *   The instances of class.
   */
  public function getInstance() {

    return $this->instance;
  }

  /**
   * Add items to VSP.
   *
   * @param array $item
   *   Use Method getEmptyItem to prepare new item.
   */
  public function addItems(array $item) {
    $defaultConfig = $this->defaultConfig;
    // Set default position if not set or default value.
    if ((isset($item['position_sticky_popup']) && ($item['position_sticky_popup'] == ''))) {
      $item['position_sticky_popup'] = $defaultConfig['position_sticky_popup'];
    }

    // Action wrapper - Position open button.
    if ((isset($item['action_wrapper']['position_open_button']) && ($item['action_wrapper']['position_open_button'] == ''))) {
      $item['action_wrapper']['position_open_button'] = $defaultConfig['position_open_button'];
    }

    // Action wrapper - Position arrow.
    if ((isset($item['action_wrapper']['position_arrow']) && ($item['action_wrapper']['position_arrow'] == ''))) {
      $item['action_wrapper']['position_arrow'] = $defaultConfig['position_arrow'];
    }
    $this->vspcSingleton->addItem($item);
  }

  /**
   * Get Empty Item.
   *
   * @return array
   *   Return and empty item to manage VSP.
   */
  public function getEmptyItem() {

    return $this->vspcSingleton->getEmptyItem();
  }

  /**
   * Get items by singleton.
   *
   * @return mixed
   *   The items from singleton.
   */
  private function getItems() {

    return $this->vspcSingleton->getItems();
  }

  /**
   * The method to add items by match path id.
   *
   * @return array
   *   Number of item fonund.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function addItemsByPathId() {

    // Get current route name.
    $pathId = $this->pathId;
    $query = \Drupal::entityQuery('v_sticky_config_entity');

    $query->condition('path_id', $pathId, '=');
    $entity_ids = $query->execute();

    if (count($entity_ids) > 0) {
      $entityManager = \Drupal::entityManager()->getStorage('v_sticky_config_entity');
      foreach ($entity_ids as $id) {
        $entity = $entityManager->load($id);
        // Define new item.
        $item = $this->getEmptyItem();
        $item['position_sticky_popup'] = $entity->get('position_sticky_popup');
        $item['collapse'] = $entity->get('collapsed');
        $item['action_wrapper']['position_open_button'] = $entity->get('position_open_button');
        $item['action_wrapper']['position_arrow'] = $entity->get('position_arrow');
        $item['action_wrapper']['tab_label'] = $entity->get('tab_label');
        $item['content']['id'] = $entity->get('id');
        $item['content']['content'] = $entity->get('content');
        $this->addItems($item);
      }
    }
    return count($entity_ids);
  }

  /**
   * The Render items method.
   *
   * @return bool|string
   *   Markup of items or false state if not exist.
   *
   * @throws \Exception
   */
  public function renderItems() {
    $items = $this->getItems();
    $markup = '';
    if (count($items) > 0) {
      foreach ($items as $position => $item) {
        $renderableArray = [
          '#theme' => 'vsauce_sticky_popup',
          '#position' => $position,
          '#item' => $item,
        ];
        $markup .= $this->renderer->render($renderableArray);
      }
      return $markup;
    }
    return FALSE;
  }

}
