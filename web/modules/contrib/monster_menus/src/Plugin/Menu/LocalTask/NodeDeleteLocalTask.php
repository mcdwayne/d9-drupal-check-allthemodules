<?php

namespace Drupal\monster_menus\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;
use Drupal\monster_menus\Form\DeleteNodeConfirmForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a local task plugin with a dynamic title.
 */
class NodeDeleteLocalTask extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return DeleteNodeConfirmForm::getMenuTitle($request->attributes->get('node'));
  }

}
