<?php

namespace Drupal\admin_menu_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\admin_menu_search\MenuTree;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
/**
 * Class AutoCompleteController.
 */
class AutoCompleteController extends ControllerBase {

  /**
   * Drupal\admin_menu_search\MenuTree definition.
   *
   * @var \Drupal\admin_menu_search\MenuTree
   */
  protected $adminMenuTreeIndex;

  /**
   * Constructs a new AutoCompleteController object.
   */
  public function __construct(MenuTree $admin_menu_tree_index) {
    $this->adminMenuTreeIndex = $admin_menu_tree_index;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('admin_menu_search.menu_tree')
    );
  }

  /**
   * Autocomplete.
   *
   * @return string
   *   Return Hello string.
   */
  public function autocomplete(Request $request) {
    $results = [];
    $menus_starting_with_keyword = [];
    $menus_containing_keyword = [];

    // Get the typed string from the URL, if it exists.
    if ($keyword = $request->query->get('q')) {
      foreach ($this->getAdminMenuTreeIndex() as $route) {
        if (($match_position = stripos($route['title'], $keyword)) !== FALSE
            && !empty(trim($keyword))
            && !empty(trim($route['name']))) {
          $value = [
            'href' => $this->getMenuUrl($route['name'], $route['parameters']),
            'value' => $route['title'],
            'label' => $route['title'],
          ];
          if ($match_position === 0) {
            $menus_starting_with_keyword[$route['title']] = $value;
          }
          elseif ($match_position > 0) {
            $menus_containing_keyword[$route['title']] = $value;
          }
        }
      }
    }

    $results = array_values($menus_starting_with_keyword + $menus_containing_keyword);

    return new JsonResponse($results);
  }

  /**
   * Method to get admin toolbar menu index array.
   *
   * @return array
   *   Array of indexed admin menu tree.
   */
  protected function getAdminMenuTreeIndex() {
    return $this->adminMenuTreeIndex->getAdminToolbarMenuIndex();
  }

  /**
   * Method to get menu url from route info.
   *
   * @param string $route_name
   *   Route name.
   * @param array $route_parameters
   *   Route parameters.
   *
   * @return string
   *   Url string.
   */
  protected function getMenuUrl($route_name, $route_parameters = []) {
    return Url::fromRoute($route_name, $route_parameters)->toString();
  }

}
