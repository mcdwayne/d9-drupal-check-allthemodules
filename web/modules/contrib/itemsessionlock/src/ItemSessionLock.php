<?php

namespace Drupal\itemsessionlock;

class ItemSessionLock {

  /**
   * Returns the plugin manager.
   *
   * @param string $type
   *   The plugin type.
   *
   * @return \Drupal\itemessionlock\Plugin\Layout\ItemSessionLockManager
   */
  public static function ItemSessionLockManager() {
    return \Drupal::service('plugin.manager.itemsessionlock');
  }

}