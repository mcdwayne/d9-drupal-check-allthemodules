<?php

namespace Drupal\og_sm_admin_menu\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Group permission event subscriber for og_sm.
 */
class GroupPermissionEventSubscriber implements EventSubscriberInterface, ContainerAwareInterface {

  use StringTranslationTrait;
  use ContainerAwareTrait;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $translation) {
    $this->setStringTranslation($translation);
  }

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
    $event->setPermission(
      new GroupPermission([
        'name' => 'access toolbar',
        'title' => $this->t('Use the administration toolbar'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ])
    );
  }

}
