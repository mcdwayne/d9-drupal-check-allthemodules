<?php
/**
 * Don't forget to define your class as a service and tag it as "event_subscriber":
 *
 * services:
 *   entity_dispatcher.example_subscribers:
 *   class: '\Drupal\entity_dispatcher\Example\ExampleEventSubscribers'
 *   tags:
 *     - { name: 'event_subscriber' }
 */
namespace Drupal\entity_dispatcher\Example;

use Drupal\entity_dispatcher\EntityDispatcherEvents;
use Drupal\entity_dispatcher\Event\EntityDeleteEvent;
use Drupal\entity_dispatcher\Event\EntityInsertEvent;
use Drupal\entity_dispatcher\Event\EntityPresaveEvent;
use Drupal\entity_dispatcher\Event\EntityUpdateEvent;
use Drupal\entity_dispatcher\Event\EntityViewEvent;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExampleEventSubscribers
 * @package Drupal\entity_dispatcher
 */
class ExampleEventSubscribers implements EventSubscriberInterface {


  /**
   * @param \Drupal\entity_dispatcher\Event\EntityViewEvent $event
   */
  public function alterEntityView(EntityViewEvent $event) {
    $entity = $event->getEntity();

    // Only do this for entities of type Node.
    if ($entity instanceof NodeInterface) {
      $build = $event->getBuild();
      $build['extra_markup'] = [
        '#markup' => 'this is extra markup',
      ];

      $event->setBuild($build);
    }
  }

  /**
   * @param \Drupal\entity_dispatcher\Event\EntityPresaveEvent $event
   */
  public function hookEntityPreSave(EntityPresaveEvent $event) {
    $entity = $event->getEntity();
    $entity->title->value = 'Overwritten';
    $event->setEntity($entity);
  }

  /**
   * @param \Drupal\entity_dispatcher\Event\EntityInsertEvent $event
   */
  public function hookEntityInsert(EntityInsertEvent $event) {
    $entity = $event->getEntity();
    // Do some fancy stuff.
  }

  /**
   * @param \Drupal\entity_dispatcher\Event\EntityUpdateEvent $event
   */
  public function hookEntityUpdate(EntityUpdateEvent $event) {
    $entity = $event->getEntity();
    // Do some fancy stuff.
  }

  /**
   * @param \Drupal\entity_dispatcher\Event\EntityDeleteEvent $event
   */
  public function hookEntityDelete(EntityDeleteEvent $event) {
    $entity = $event->getEntity();
    // Do some fancy stuff.
  }


  /**
   * @inheritdoc
   */
  static function getSubscribedEvents() {
    return [
      EntityDispatcherEvents::ENTITY_VIEW => [
        ['alterEntityView'],
      ],
      EntityDispatcherEvents::ENTITY_PRE_SAVE => [
        ['hookEntityPreSave'],
      ],
      EntityDispatcherEvents::ENTITY_INSERT => [
        ['hookEntityInsert'],
      ],
      EntityDispatcherEvents::ENTITY_UPDATE => [
        ['hookEntityUpdate'],
      ],
      EntityDispatcherEvents::ENTITY_DELETE => [
        ['hookEntityDelete'],
      ],
    ];
  }

}