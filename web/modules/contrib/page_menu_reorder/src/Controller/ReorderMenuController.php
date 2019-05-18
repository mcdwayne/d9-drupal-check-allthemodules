<?php

namespace Drupal\page_menu_reorder\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\AccessResult;

/**
 * Class ReorderMenuController.
 */
class ReorderMenuController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Node $node) {
    $title = $node->getTitle();
    return $title;
  }

  /**
   * Checks if a page has menu items.
   *
   * @return bool
   *   Returns true if a node has menu items, false on no menu items
   */
  public function menuCheck(Node $node) {
    // Menu name.
    $menu_name = 'main';

    // Get nodes menu item from main menu.
    $menu_item = db_select('menu_tree', 'ml')
      ->condition('ml.route_param_key', 'node=' . $node->id())
      ->condition('ml.menu_name', $menu_name)
      ->fields('ml', ['mlid', 'has_children'])
      ->execute()
      ->fetchObject();

    // Check if the menu item has children.
    if (is_object($menu_item)) {
      if ($menu_item->has_children) {
        return TRUE;
      }
      else {
        // Invalidate the static caches of the node.
        Cache::invalidateTags($node->getCacheTags());
      }
    }
    return FALSE;
  }

  /**
   * Custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param \Drupal\node\Entity\Node $node
   *   The node entity to clone.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(AccountInterface $account, Node $node) {
    // Get current node type.
    $current_node_type = $node->getType();
    // Get allowable CT.
    $config = \Drupal::config('page_menu_reorder.settings');
    $types = $config->get('enabled_types', []);

    // Check if any CT is enabled
    if (!$types) {
      return AccessResult::forbidden();
    }

    // Check if user has permission and allowable CT.
    if ($account->hasPermission('reorder section menu') && in_array($current_node_type, $types)) {
      // Check if menu items present.
      if ($this->menuCheck($node)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}
