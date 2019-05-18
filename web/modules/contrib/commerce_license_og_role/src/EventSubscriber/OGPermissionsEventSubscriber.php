<?php

namespace Drupal\commerce_license_og_role\EventSubscriber;

use Drupal\content_moderation\Permissions;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides an OG permissions to grant roles in a group with licenses.
 */
class OGPermissionsEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [
        ['providePermissions'],
      ],
    ];
  }

  /**
   * Provides OG permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function providePermissions(PermissionEventInterface $event) {
    $event->setPermissions([
      new GroupPermission([
        'name' => 'grant group roles with licenses',
        'title' => t('Grant group roles with licenses'),
        'description' => t('Grant roles in this group when creating a Commerce License product.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
        'restrict access' => TRUE,
      ]),
    ]);
  }

}
