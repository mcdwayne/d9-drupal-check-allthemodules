<?php

namespace Drupal\menu_per_user;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

class MenuPerUserLinkTreeManipulator extends DefaultMenuLinkTreeManipulators {

  /**
   * Checks access for one menu link instance.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $instance
   *   The menu link instance.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $result = parent::menuLinkCheckAccess($instance);
    $url = $instance->getUrlObject();
    if ($url->isRouted()) {
      $uid = \Drupal::currentUser()->id();
      $access_check = menu_per_user_check_menu_name_uid($url->getRouteName(), $uid);
      if ($access_check) {
        $result = $result->andIf(AccessResult::forbidden());
      }
    }
    return $result;
  }

}
