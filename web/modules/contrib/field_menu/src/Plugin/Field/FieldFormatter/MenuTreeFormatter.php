<?php

namespace Drupal\field_menu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_menu_tree_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_menu_tree_formatter",
 *   module = "field_menu",
 *   label = @Translation("Menu Tree formatter"),
 *   field_types = {
 *     "field_menu"
 *   }
 * )
 */
class MenuTreeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $menu_key_value_arr = explode(':', $item->menu_item_key);
      $menu_name = (isset($menu_key_value_arr[0]) && $menu_key_value_arr[0]) ? $menu_key_value_arr[0]:null;
      $parent = (isset($menu_key_value_arr[1]) && $menu_key_value_arr[1]) ? $menu_key_value_arr[1]:null;
      $menu_link = (isset($menu_key_value_arr[2]) && $menu_key_value_arr[2]) ? $menu_key_value_arr[2]:null;

      $menu_route = ($parent == 'menu_link_content') ?  $parent . ':' . $menu_link:$parent;

      $menu_parameters = new \Drupal\Core\Menu\MenuTreeParameters();
      $menu_parameters->setRoot($menu_route);
      $menu_parameters->onlyEnabledLinks();
      if($item->max_depth > 0){
        $menu_parameters->setMaxDepth($item->max_depth);
      }
      if(!$item->include_root){
        $menu_parameters->excludeRoot();
      }

      $menu_tree_service = \Drupal::service('menu.link_tree');
      $tree = $menu_tree_service->load($menu_name, $menu_parameters);

      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];
      $tree = $menu_tree_service->transform($tree, $manipulators);
      $render_array = $menu_tree_service->build($tree);
      
      $markup = drupal_render($render_array);
      $menu_title = trim($item->menu_title);
      if($menu_title){
        $markup = '<h2 class="menu-title">' . $menu_title . '</h2>' . $markup;
      }
      $elements[$delta] = ['#markup' => $markup];
    }

    return $elements;
  }

}
