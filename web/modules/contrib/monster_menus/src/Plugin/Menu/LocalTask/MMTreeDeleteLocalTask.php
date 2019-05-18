<?php

namespace Drupal\monster_menus\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\monster_menus\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local task plugin with a dynamic title.
 */
class MMTreeDeleteLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return DefaultController::menuGetTitleSettingsDelete($request->attributes->get('mm_tree'));
  }

}
