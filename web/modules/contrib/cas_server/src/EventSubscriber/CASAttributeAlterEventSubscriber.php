<?php

namespace Drupal\cas_server\EventSubscriber;

use Drupal\cas_server\Configuration\ConfigHelper;
use Drupal\cas_server\Event\CASAttributesAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class CASAttributeAlterEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigHelper $configHelper) {
    $this->configHelper = $configHelper;
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[CASAttributesAlterEvent::CAS_ATTRIBUTES_ALTER_EVENT][] = [
      'onCASAttributeAlter',
      100,
    ];
    return $events;
  }

  public function onCASAttributeAlter(CASAttributesAlterEvent $event) {
    $eventAttributes = [];
    $attributes = $this->configHelper->getAttributesForService($event->getTicket()
      ->getService());
    if (!empty($attributes)) {
      foreach ($attributes as $attr) {
        foreach ($event->getUser()->get($attr)->getValue() as $value) {
          $eventAttributes[$attr] = $value['value'];
        }
      }
    }
    $event->setAttributes($eventAttributes);
  }

}
