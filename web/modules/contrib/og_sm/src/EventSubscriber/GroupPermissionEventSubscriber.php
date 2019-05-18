<?php

namespace Drupal\og_sm\EventSubscriber;

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
        'name' => 'administer site',
        'title' => $this->t('Administer Site'),
        'description' => $this->t('View the Site administration pages.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ])
    );
    $event->setPermission(
      new GroupPermission([
        'name' => 'view the administration theme',
        'title' => $this->t('View the administration theme'),
        'description' => $this->t('This is only used when the site is configured to use a separate administration theme on the Appearance page.'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ])
    );
  }

}
