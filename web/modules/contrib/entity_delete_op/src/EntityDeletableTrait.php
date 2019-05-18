<?php

namespace Drupal\entity_delete_op;

/**
 * Utility trait for common implementations of deletable entities.
 *
 * @see \Drupal\entity_delete_op\EntityDeletableInterface
 */
trait EntityDeletableTrait {

  /**
   * Checks if the entity is marked as deleted.
   *
   * @return bool
   *   Returns TRUE if marked as deleted, otherwise FALSE.
   *
   * @see \Drupal\entity_delete_op\EntityDeletableInterface::isDeleted()
   */
  public function isDeleted() {
    if ($this->get('deleted')->isEmpty()) {
      return FALSE;
    }

    $value = $this->get('deleted')->first()->getValue();
    if (isset($value['value'])) {
      return filter_var($value['value'], FILTER_VALIDATE_BOOLEAN);
    }

    return FALSE;
  }

  /**
   * Marks the entity as deleted or not.
   *
   * @param bool $value
   *   A boolean indicating whether entity should be marked as deleted.
   *
   * @return \Drupal\entity_delete_op\EntityDeletableTrait
   *   Self.
   *
   * @see \Drupal\entity_delete_op\EntityDeletableInterface::setIsDeleted()
   */
  public function setIsDeleted($value) {
    $this->set('deleted', ((bool) $value));
    return $this;
  }

}
