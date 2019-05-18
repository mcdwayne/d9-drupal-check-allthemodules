<?php

namespace Drupal\avatars_ringicon;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\avatars\Event\AvatarKitEvents;
use Drupal\avatars\Event\AvatarKitServiceDefinitionAlterEvent;
use Drupal\avatars_ringicon\Plugin\Avatars\Service\Ringicon;

/**
 * Used to alter avatar service plugin definitions.
 */
class AvatarsRingiconAvatarServicePluginInfoAlter implements EventSubscriberInterface {

  /**
   * Alter avatar service plugin definitions.
   *
   * @param \Drupal\avatars\Event\AvatarKitServiceDefinitionAlterEvent $event
   *   The event.
   */
  public function alterServiceInfo(AvatarKitServiceDefinitionAlterEvent $event) {
    // Avatar Kit service plugin manager will pick up common services simply by
    // making these services use a real class instead of an abstract.
    $definitions = $event->getDefinitions();

    $definitions['avatars_ak_common:ringicon']['class'] = Ringicon::class;
    // A file entity is created by the plugin.
    $definitions['avatars_ak_common:ringicon']['files'] = TRUE;

    $event->setDefinitions($definitions);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AvatarKitEvents::PLUGIN_SERVICE_ALTER][] = ['alterServiceInfo'];
    return $events;
  }

}
