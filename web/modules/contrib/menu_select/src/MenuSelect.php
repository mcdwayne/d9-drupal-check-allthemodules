<?php

namespace Drupal\menu_select;

use Drupal;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;

/**
 * Defines a class with methods for Menu Select.
 */
class MenuSelect {

  /**
   * Retrieves the menus available to this content type.
   *
   * @param string $bundle
   *   The machine name of the content type.
   *
   * @return array
   *   Available menus with machine names.
   */
  public static function getAvailableMenus($bundle) {
    $node_type = NodeType::load($bundle);
    $menus = $node_type->getThirdPartySetting('menu_ui', 'available_menus');

    $menu_names = menu_ui_get_menus();

    $menu_options = array();
    foreach ($menus as $menu) {
      $menu_options[$menu] = $menu_names[$menu];
    }
    return $menu_options;
  }

  /**
   * Function to build a menu tree from a menu name.
   *
   * @param string $menu_name
   *   Menu name to use.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   Array of the menu tree.
   */
  public static function menuTreeMachineNameLoad($menu_name) {
    $menu_tree = Drupal::menuTree($menu_name);
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
    return $menu_tree->transform($tree, $manipulators);
  }

  /**
   * Function to generate an array of the full menu.
   *
   * Output keys of each menu link id to it's link title.
   *
   * @param array $menu_tree
   *   Array of the menu tree.
   * @param string $menu_name
   *   String of the menu name.
   *
   * @return mixed
   *   The menu structure.
   */
  private static function buildNestedMenu(array $menu_tree, $menu_name, $current_node_route) {
    foreach ($menu_tree as $data) {
      $title = $data->link->getTitle();
      $link = $menu_name . ':' . $data->link->getPluginId();
      $nested_menu[$link]['data'] = MenuSelect::generateLink($title, $menu_name . ':' . $data->link->getPluginId());
      if (!empty($data->subtree)) {
        $nested_menu[$link]['children'] = MenuSelect::buildNestedMenu($data->subtree, $menu_name, $current_node_route);
      }
    }
    unset($nested_menu[$menu_name . ':' . $current_node_route]);
    return $nested_menu;
  }

  /**
   * Generates a valid Drupal 8 link.
   *
   * @param string $title
   *   The link title to use.
   * @param string $mkey
   *   The menu key of the link.
   *
   * @return array|\mixed[]
   *   The link object.
   */
  private static function generateLink($title, $mkey) {
    $url = Url::fromRoute('<current>');
    $link_options = array(
      'attributes' => array(
        'class' => array(
          'menu-select-menu-link',
          'js-menu-select-menu-link',
        ),
        'data-mkey' => $mkey,
      ),
      'fragment' => 'menu-select-parent-menu',
    );
    $url->setOptions($link_options);
    $link = Link::fromTextAndUrl($title, $url)->toRenderable();
    $link['#title'] = $link['#title'];
    return $link;
  }

  /**
   * Builds a renderable array of the menu tree(s).
   *
   * @param array $menu_tree
   *   The menu tree.
   * @param string $menu_name
   *   The menu name.
   * @param FormStateInterface $form_state
   *   The current state of the edit form.
   *
   * @return array
   *   A renderable array.
   */
  public static function buildRenderedMenu(array $menu_tree, $menu_name, FormStateInterface $form_state) {
    $current_node_id = $form_state->getFormObject()->getEntity()->id();
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $current_node_route = key($menu_link_manager->loadLinksByRoute('entity.node.canonical', array('node' => $current_node_id)));

    $menu_machine = $menu_name['machine'];
    $menu_clean = $menu_name['clean'];
    $items[$menu_machine . ':'] = array(
      'data' => MenuSelect::generateLink($menu_clean . ' (' . t('menu') . ')', $menu_machine . ':'),
    );
    if (!empty($menu_tree)) {
      $items[$menu_machine . ':']['children'] = MenuSelect::buildNestedMenu($menu_tree, $menu_machine, $current_node_route);
    }
    $renderableMenu = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#wrapper_attributes' => [
        'class' => [
          'menu-select-menu-hierarchy',
          'js-menu-select-menu-hierarchy',
        ],
      ],
      '#attributes' => [
        'class' => [
          'menu-select-menu-level',
          'js-menu-select-menu-level',
        ],
      ],
      '#items' => $items,
    ];
    return $renderableMenu;
  }

}
