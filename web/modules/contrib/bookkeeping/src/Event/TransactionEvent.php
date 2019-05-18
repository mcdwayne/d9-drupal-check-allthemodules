<?php

namespace Drupal\bookkeeping\Event;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base event raised when preparing a transaction posting.
 */
abstract class TransactionEvent extends Event {

  /**
   * The generator.
   *
   * @var string
   */
  protected $generator;

  /**
   * The value of the transaction.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $value;

  /**
   * Whether there have been modifications.
   *
   * @var bool
   */
  protected $modified = FALSE;

  /**
   * Whether to prevent creating the transaction.
   *
   * @var bool
   */
  protected $prevented = FALSE;

  /**
   * Related entities to add to the transaction.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $related = [];

  /**
   * Construct the Transaction event.
   *
   * @param string $generator
   *   The generator.
   * @param \Drupal\commerce_price\Price $value
   *   The value we are posting.
   */
  public function __construct(string $generator, Price $value) {
    $this->generator = $generator;
    $this->value = $value;
  }

  /**
   * Get the generator.
   *
   * @return string
   *   The generator.
   */
  public function getGenerator(): string {
    return $this->generator;
  }

  /**
   * Get the value of the transaction.
   *
   * @return \Drupal\commerce_price\Price
   *   The value as a price object.
   */
  public function getValue(): Price {
    return $this->value;
  }

  /**
   * Change the value of the transaction.
   *
   * @param \Drupal\commerce_price\Price $value
   *   The new value of the transaction.
   *
   * @return $this
   */
  public function setValue(Price $value) {
    $this->modified = TRUE;
    $this->value = $value;
    return $this;
  }

  /**
   * Check if there has been a modification.
   *
   * @return bool
   *   Whether there has been a modification.
   */
  public function isModified(): bool {
    return $this->modified;
  }

  /**
   * Check whether we should be preventing the transaction.
   *
   * @return bool
   *   Whether to prevent the transaction.
   */
  public function isPrevented(): bool {
    return $this->prevented;
  }

  /**
   * Indicate that we should prevent this transaction being posted.
   *
   * Will also stop propagation of the event.
   */
  public function prevent(): void {
    $this->prevented = TRUE;
    $this->stopPropagation();
  }

  /**
   * Get additional related entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The related entities.
   */
  public function getRelated(): array {
    return $this->related;
  }

  /**
   * Add a related entity for the transaction.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The related entity to add.
   *
   * @return $this
   */
  public function addRelated(EntityInterface $entity) {
    // Check whether this has already been added.
    foreach ($this->related as $related) {
      if ($related->getEntityTypeId() == $entity->getEntityTypeId() && $related->id() == $entity->id()) {
        return $this;
      }
    }

    $this->related[] = $entity;
    return $this;
  }

}
