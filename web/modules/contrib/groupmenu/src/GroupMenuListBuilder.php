<?php

namespace Drupal\groupmenu;

use Drupal\group\Entity\GroupContentType;
use \Drupal\menu_ui\MenuListBuilder;

/**
 * Override the default menu overview to exclude group menus.
 */
class GroupMenuListBuilder extends MenuListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $plugin_id = 'group_menu:menu';
    $group_content_types = GroupContentType::loadByContentPluginId($plugin_id);
    if (empty($group_content_types)) {
      return parent::getEntityIds();
    }

    // Load all the group menu content to exclude.
    /** @var \Drupal\group\Entity\GroupContentInterface[] $group_contents */
    $group_contents = \Drupal::entityTypeManager()
      ->getStorage('group_content')
      ->loadByProperties([
        'type' => array_keys($group_content_types),
      ]);
    $menus = [];
    foreach ($group_contents as $group_content) {
      $menu = $group_content->getEntity();
      if (!$menu) {
        continue;
      }
      $menu_name = $menu->id();
      if (!in_array($menu_name, $menus)) {
        $menus[] = $menu_name;
      }
    }

    // Load all menus not used as group content.
    $query = $this->getStorage()->getQuery()
      ->condition($this->entityType->getKey('id'), $menus, 'NOT IN')
      ->sort($this->entityType->getKey('id'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
