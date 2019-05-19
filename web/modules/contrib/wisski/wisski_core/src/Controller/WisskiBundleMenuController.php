<?php

namespace Drupal\wisski_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\Entity\Menu;

use Drupal\wisski_core\Entity\WisskiBundle;

/** Callbacks for actions that manipulate the WissKI menus
 *
 */
class WisskiBundleMenuController extends ControllerBase {
  
  public function recreateMenuItems ($menu_name = NULL) {
    $this->emptyMenus($menu_name);
    $this->updateMenus($menu_name);
    drupal_set_message($this->t("The WissKI menus' items have been recreated"));
    return $this->redirect('<front>');
  }
  

  public function emptyMenus ($menu_name = NULL) {
    
    if ($menu_name === NULL) {
      $menus = (array_keys(WisskiBundle::getWissKIMenus()));
    }
    else {
      $menus = [$menu_name => $menu_name];
    }
    foreach ($menus as $menu_name) {
      // get all menu items/links of the menu
      $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['menu_name' => $menu_name]);
      foreach ($menu_links as $menu_link) {
        $menu_link->delete();
      }
    }

  }


  public function updateMenus ($menu_name) {
    $bundles = WisskiBundle::loadMultiple();
    $menus = WisskiBundle::getWissKIMenus();
    foreach ($bundles as $bundle) {
      foreach ($menus as $menu_name => $route_name) {
        $bundle->addBundleToMenu($menu_name, $route_name);
      }
    }
  }
  
}
