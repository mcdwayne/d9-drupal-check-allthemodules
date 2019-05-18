<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;

/**
 * Defines an entity reference guard.
 */
final class ProtectReferentGuard implements GuardInterface {

  /**
   * The event the guard is for.
   *
   * @var string
   */
  private $event;

  /**
   * Constructs a new instance.
   *
   * @param string $event
   *   The event the guard is for.
   */
  public function __construct(string $event) {
    $this->event = $event;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvent(): string {
    return $this->event;
  }

  /**
   * {@inheritdoc}
   */
  public function guardReference(EntityReference $entityReference) {
    throw new ProtectedReferenceException($entityReference);
  }

}
