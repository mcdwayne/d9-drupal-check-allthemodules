<?php

namespace Drupal\contentserialize\EventSubscriber;

use Drupal\contentserialize\Event\ImportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add a missing reference fixer to imports.
 *
 * @package Drupal\contentserialize\EventSubscriber
 */
class MissingReferenceSubscriber implements EventSubscriberInterface {

  /**
   * The key to store the reference fixer under in the context.
   *
   * @var string
   */
  const CONTEXT_KEY = 'contentserialize.missing_reference_fixer';

  /**
   * The missing reference fixer factory.
   *
   * @var \Drupal\contentserialize\MissingReferenceFixerFactory
   */
  protected $missingReferenceFixerFactory;

  /**
   * Create a missing reference subscriber.
   *
   * @param \Drupal\contentserialize\MissingReferenceFixerFactory $missing_reference_fixer_factory
   *   The missing reference fixer factory.
   */
  public function __construct($missing_reference_fixer_factory) {
    $this->missingReferenceFixerFactory = $missing_reference_fixer_factory;
  }

  /**
   * Add a missing reference fixer to the serialization context.
   *
   * @param \Drupal\contentserialize\Event\ContextEvent $event
   *   The import event.
   */
  public function addMissingReferenceFixer($event) {
    $event->context[static::CONTEXT_KEY] = $this->missingReferenceFixerFactory->create();
  }

  /**
   * Register a missing reference with the fixer.
   *
   * @param \Drupal\contentserialize\Event\MissingReferenceEvent $event
   *   The missing reference event.
   */
  public function registerMissingReference($event) {
    /** @var \Drupal\contentserialize\MissingReferenceFixer $missing_reference_fixer */
    $missing_reference_fixer = $event->context[static::CONTEXT_KEY];
    $missing_reference_fixer->register(
      $event->getEntityType(),
      $event->getUuid(),
      $event->getTargetEntityType(),
      $event->getTargetUuid(),
      $event->getCallback()
    );
  }

  /**
   * Fix any missing references.
   *
   * @param \Drupal\contentserialize\Event\ContextEvent $event
   *   The import event.
   */
  public function fixMissingReferences($event) {
    /** @var \Drupal\contentserialize\MissingReferenceFixer $fixer */
    $fixer = $event->context[static::CONTEXT_KEY];
    $fixer->fix();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ImportEvents::START][] = ['addMissingReferenceFixer'];
    $events[ImportEvents::MISSING_REFERENCE][] = ['registerMissingReference'];
    $events[ImportEvents::STOP][] = ['fixMissingReferences'];
    return $events;
  }

}