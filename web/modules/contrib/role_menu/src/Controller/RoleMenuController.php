<?php

namespace Drupal\role_menu\Controller;


use Drupal\Core\Controller\ControllerBase;

class RoleMenuController extends ControllerBase {


  /**
   * @param $menu_id
   *
   * @return mixed
   */
  public function menuBlockPage($menu_id) {
    return \Drupal::service('role_menu.manager')->getMenuBlockContents($menu_id);
  }


}