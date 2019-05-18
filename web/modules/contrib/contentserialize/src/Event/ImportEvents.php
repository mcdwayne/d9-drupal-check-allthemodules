<?php

namespace Drupal\contentserialize\Event;

/**
 * Defines import events for content serialization.
 */
final class ImportEvents {

  /**
   * Name of event fired when an import starts.
   * 
   * The serialization context can be accessed via the event.
   * 
   * @Event
   * 
   * @see \Drupal\contentserialize\Event\ContextEvent
   * @see \Drupal\contentserialize\EventSubscriber\MissingReferenceSubscriber::addMissingReferenceFixer()
   * 
   * @var string
   */
  const START = 'contentserialize.import.start';

  /**
   * Name of event fires when an import stops.
   *
   * The serialization context can be accessed via the event.
   * 
   * @Event
   * 
   * @see \Drupal\contentserialize\Event\ContextEvent
   * @see \Drupal\contentserialize\EventSubscriber\MissingReferenceSubscriber::fixMissingReferences()
   *
   * @var string
   */
  const STOP = 'contentserialize.import.stop';

  /**
   * Name of event fired when a referenced entity cannot be found.
   *
   * The serialization context can be accessed via the event.
   * 
   * @Event
   * 
   * @see \Drupal\contentserialize\Event\MissingReferenceEvent
   * @see \Drupal\contentserialize\EventSubscriber\MissingReferenceSubscriber::registerMissingReference()
   *
   * @var string
   */
  const MISSING_REFERENCE = 'contentserialize.import.missing_reference';

}
