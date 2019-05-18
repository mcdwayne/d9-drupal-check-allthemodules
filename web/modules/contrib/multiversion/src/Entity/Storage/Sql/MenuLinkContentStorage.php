<?php

namespace Drupal\multiversion\Entity\Storage\Sql;

use Drupal\multiversion\Entity\Storage\ContentEntityStorageInterface;
use Drupal\multiversion\Entity\Storage\ContentEntityStorageTrait;

// Support Drupal 8.7.0 which introduced a dedicated entity storage class for
// the menu_link_content entity type.
if (class_exists('\Drupal\menu_link_content\MenuLinkContentStorage')) {
  class_alias('\Drupal\menu_link_content\MenuLinkContentStorage', '\CoreMenuLinkContentStorage');
}
else {
  class_alias('\Drupal\Core\Entity\Sql\SqlContentEntityStorage', '\CoreMenuLinkContentStorage');
}

/**
 * Storage handler for menu link content.
 */
class MenuLinkContentStorage extends \CoreMenuLinkContentStorage implements ContentEntityStorageInterface {

  use ContentEntityStorageTrait {
    delete as deleteEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    $this->deleteEntities($entities);

    // Remove the deleted entity as parent for all children.
    foreach ($entities as $entity) {
      $children = $this->loadByProperties(['parent' => $entity->getPluginId()]);
      foreach ($children as $child) {
        $child->parent->value = '';
        $child->save();
      }
    }

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

    foreach ($entities as $menu_link) {
      // Remove link definition from the menu tree storage.
      $menu_link_manager->removeDefinition($menu_link->getPluginId(), FALSE);
    }
  }

}
