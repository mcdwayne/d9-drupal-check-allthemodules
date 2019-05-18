<?php

namespace Drupal\og_sm_menu\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Group permission event subscriber for og_sm_menu.
 */
class GroupPermissionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideDefaultOgPermissions']],
    ];
  }

  /**
   * Provides default OG permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideDefaultOgPermissions(PermissionEventInterface $event) {
    $menus = \Drupal::entityTypeManager()
      ->getStorage('ogmenu_instance')
      ->loadMultiple();

    foreach ($menus as $menu) {
      /* @var \Drupal\og_menu\OgMenuInstanceInterface $menu */
      $permission = new GroupPermission([
        'name' => "administer {$menu->getType()} menu items",
        'title' => t('Administer %menu_name menu items'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]);

      $event->setPermission($permission);
    }
  }

}
