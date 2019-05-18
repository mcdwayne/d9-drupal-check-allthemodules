<?php

/**
 * @file
 * Provides controller for menus edition.
 *
 * Sponsored by: www.freelance-drupal.com
 */

namespace Drupal\feadmin_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for feadmin_menu module routes.
 */
class FeAdminMenuController extends ControllerBase {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Constructs a FeAdminMenuController object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface
   *   The menu link manager service.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface
   *   The menu link tree service.
   */
  public function __construct(LoggerInterface $logger, MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $menu_tree) {
    $this->logger = $logger;
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('Front-End Administration'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree')
    );
  }

  /**
   * Save menus and menu items after there sorting.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function sortMenus(Request $request) {

    // Retrieve POST content.
    $content = $request->getContent();

    // In case of ping with no data: return HTTP 500.
    if (empty($content)) {
      $this->logger->warning('sortMenus pinged with no data.');
      return new JsonResponse(null, 500);
    }
    // 2nd param to get as array
    $params = json_decode($content, TRUE);

    // Retrieve the menu that needs an update.
    $tree = $this->menuTree->load($params['menu'], new MenuTreeParameters());

    // Iterate through those blocks and save the change.
    $menu_items = $params['menu_items'];

    /** @var \Drupal\Core\Menu\MenuLinkInterface[] $entities */
    foreach ($menu_items as $weight => $menu_item) {
      if (isset($tree[$menu_item])) {
        $this->menuLinkManager->updateDefinition($tree[$menu_item]->link->getPluginId(), array('weight' => $weight));
      }
    }

    // Return a positive feedback.
    return new JsonResponse('The menu settings have been updated.');
  }

  /**
   * Delete menu items.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function deleteAction(Request $request) {

  }
}



