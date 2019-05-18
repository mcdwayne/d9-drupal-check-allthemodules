<?php

namespace Drupal\menu_child_item\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a route controller for a form for menu link content entity creation.
 */
class MenuChildController extends ControllerBase {

  /**
   * Provides the menu link creation form.
   *
   * @param $menu_link_content
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addChildLink($menu_link_content) {
    $menu_content = explode(':', $menu_link_content);
    $query = \Drupal::database()->select('menu_tree', 'mt');
    $query->fields('mt', ['menu_name']);
    $query->condition('mt.id', $menu_link_content);
    $menu_name = $query->execute()->fetchField();

    $menu_link = $this->entityManager()->getStorage('menu_link_content')->create([
      'id' => '',
      'parent' => $menu_link_content,
      'menu_name' => $menu_name ?: 'main',
      'bundle' => 'menu_link_content',
    ]);
    return $this->entityFormBuilder()->getForm($menu_link);
  }

}
