<?php

namespace Drupal\services_menu\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a service resource for menus.
 *
 * @RestResource(
 *   id = "services_menu",
 *   label = @Translation("Services for menus."),
 *   uri_paths = {
 *     "canonical" = "/services/menu/{menu}"
 *   }
 * )
 */
class ServicesMenuResource extends ResourceBase {
  /**
   * @param null $menu_name
   * @return ResourceResponse
   */
  public function get($menu_name = NULL) {
    $menu_tree = \Drupal::menuTree();
    $generator = \Drupal::urlGenerator();

    // Load the tree based on this set of parameters.
    $tree = $menu_tree->load($menu_name, new \Drupal\Core\Menu\MenuTreeParameters());

    // Transform the tree using the manipulators you want.
    $manipulators = array(
      // Only show links that are accessible for the current user.
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      // Use the default sorting of menu links.
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $menu_tree->transform($tree, $manipulators);

    foreach ($tree as $element) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      $path = $generator->getPathFromRoute($link->getRouteName());

      $menu[$link->getRouteName()]['title'] = $link->getTitle();
      $menu[$link->getRouteName()]['url'] = $path;

      if ($element->subtree) {
        $subtree = $menu_tree->build($element->subtree);

        foreach ($subtree['#items'] as $key => $value) {
          $path = $generator->getPathFromRoute($key);
          $menu[$key]['title'] = $value['title'];
          $menu[$key]['url'] = $path;
          // For getting submenu list
          foreach ($subtree['#items'][$key]['below'] as $subkey => $subvalue) {
            $path = $generator->getPathFromRoute($subkey);
            $menu[$key]['below'][$subkey]['title'] = $subvalue['title'];
            $menu[$key]['below'][$subkey]['url'] = $path;
          }
        }
      }
    }
    $response = new ResourceResponse($menu);
    $response->addCacheableDependency($menu);
    return $response;
  }
}