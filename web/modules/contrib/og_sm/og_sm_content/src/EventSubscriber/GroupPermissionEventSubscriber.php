<?php

namespace Drupal\og_sm_content\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Group permission event subscriber for og_sm_content.
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
    $event->setPermissions([
      new GroupPermission([
        'name' => 'access content overview',
        'title' => t('Access content overview'),
        'description' => t('Get an overview of all Site content.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
      new GroupPermission([
        'name' => 'access my content overview',
        'title' => t('Access my content overview'),
        'description' => t('Get an overview of all Site content created by the current user.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
    ]);
  }

}
