<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;

/**
 * Deletes references when their referents are deleted.
 */
final class DeleteReferenceGuard implements GuardInterface {

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
    $referee = $entityReference->getReferee();
    if (!$referee) {
      return;
    }
    $referee->get($entityReference->getFieldName())
      ->filter(function ($item) use ($entityReference) {
                return $entityReference->getReferent()->id() != $item->target_id;
      });
    $referee->save();
  }

}
