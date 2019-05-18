<?php

namespace Drupal\menu_select\Controller;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_select\MenuSelect;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a controller class with methods for auto complete.
 */
class MenuSelectAutocompleteController {

  /**
   * Retrieve possible menu links.
   *
   * @param string $keyword
   *   Keyword to search for.
   *
   * @return array
   *   Matches.
   */
  public function getPossibleMenuLinks($keyword, $available_menus) {
    $options = array();
    $possibilities = array();
    foreach ($available_menus as $menu_name => $menu_title) {
      $menu_tree = \Drupal::menuTree($menu_name);
      $parameters = new MenuTreeParameters();
      $parameters->setMaxDepth(99999);
      $parameters->setMinDepth(1);
      $tree = $menu_tree->load($menu_name, $parameters);
      $manipulators = array(
        // Only show links that are accessible for the current user.
        array('callable' => 'menu.default_tree_manipulators:checkAccess'),
        // Use the default sorting of menu links.
        array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
      );
      $tree = $menu_tree->transform($tree, $manipulators);
      $this->menuParentsOptions($tree, $menu_name, $possibilities);
      foreach ($possibilities as $key => $possibility) {
        if (stripos($possibility, $keyword) !== FALSE) {
          $options[$key] = $possibility;
        }
      }
    }
    return $options;
  }

  /**
   * Function to get parent options given a menu.
   *
   * @param array $tree
   *   The menu tree to work on.
   * @param string $menu_name
   *   The menu name.
   * @param array $options
   *   Possible parent options.
   */
  private function menuParentsOptions(array $tree, $menu_name, array &$options) {
    foreach ($tree as $data) {
      $title = $data->link->getTitle();
      $options[$menu_name . ':' . $data->link->getPluginId()] = $title;
      if (!empty($data->subtree)) {
        $this->menuParentsOptions($data->subtree, $menu_name, $options);
      }
    }
  }

  /**
   * Returns some auto complete content.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response.
   */
  public function autocomplete($type, Request $request) {
    $available_menus = MenuSelect::getAvailableMenus($type);

    $keyword = $request->query->get('q');

    $data = $this->getPossibleMenuLinks($keyword, $available_menus);

    $matches = array();
    foreach ($data as $key => $label) {
      $matches[] = array('value' => (string) $key, 'label' => $label);
    }
    return new JsonResponse($matches);
  }

}
