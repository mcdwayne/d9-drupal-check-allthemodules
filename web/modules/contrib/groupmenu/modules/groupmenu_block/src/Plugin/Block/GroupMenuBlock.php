<?php

namespace Drupal\groupmenu_block\Plugin\Block;

use Drupal\group\Entity\GroupContent;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a block for displaying group menus.
 *
 * @Block(
 *   id = "groupmenus",
 *   admin_label = @Translation("Group menus"),
 *   context = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE),
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class GroupMenuBlock extends BlockBase {

  /**
   * Gets group menu names from group ID.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group you want to load menus for.
   *
   * @return \Drupal\system\MenuInterface[]
   *   An array of menu objects keyed by menu name.
   */
  protected function getGroupMenus(GroupInterface $group) {
    return \Drupal::service('groupmenu.menu')->loadUserGroupMenusByGroup('view', $group->id());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get the associated group content for the current node.
    $node = $this->getContextValue('node');
    if ($node) {
      $group_contents = GroupContent::loadByEntity($node);

      $menus = [];
      // Get each group this node belongs to.
      foreach ($group_contents as $group_content) {
        /** @var \Drupal\group\Entity\GroupContentInterface $group_content */
        $group = $group_content->getGroup();
        // Make an array of menus to render.
        $menus = array_merge($menus, $this->getGroupMenus($group));
      }
    }
    else {
      // Not on a node page, but try to get the group anyway.
      $group = $this->getContextValue('group');
      if ($group) {
        $menus = $this->getGroupMenus($group);
      }
      else {
        return [];
      }
    }

    // Render the menus.
    $build = [];
    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    foreach ($menus as $menu_name => $menu) {
      $tree = \Drupal::menuTree()->load($menu_name, $parameters);
      $tree = \Drupal::menuTree()->transform($tree, $manipulators);
      $build[] = \Drupal::menuTree()->build($tree);
    }

    return $build;
  }

}
