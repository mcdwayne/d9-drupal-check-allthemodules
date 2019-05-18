<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;

/**
 * Defines an entity reference guard.
 */
interface GuardInterface {

  /**
   * Gets the event the guard is for.
   *
   * @return string
   *   An Event::* event (operation) name.
   *
   * @see \Drupal\erg\Event
   */
  public function getEvent(): string;

  /**
   * Guards an entity reference.
   *
   * @return \Drupal\erg\EntityReference
   *   The entity reference to guard.
   *
   * @throws \Drupal\erg\Guard\GuardExceptionInterface
   *   Thrown if the guard wants to prevent the dispatched event from
   *   completing.
   */
  public function guardReference(EntityReference $entityReference);

}
