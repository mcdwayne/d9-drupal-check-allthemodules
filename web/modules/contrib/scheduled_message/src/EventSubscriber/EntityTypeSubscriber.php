<?php

namespace Drupal\scheduled_message\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\scheduled_message
 */
class EntityTypeSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['entity_type.definition.update'] = ['entityTypeDefinitionUpdate'];

    return $events;
  }

  /**
   * Update the entityTypeDefinition.
   *
   * This method is called whenever the entity_type.definition.update event is
   * dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   Event that was fired.
   */
  public function entityTypeDefinitionUpdate(Event $event) {
    // TODO Apparently this event is never actually dispatched. Using hook
    // instead.
    drupal_set_message('Event entity_type.definition.update thrown by Subscriber
      in module scheduled_message.', 'status', TRUE);
  }

}
