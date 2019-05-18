<?php

namespace Drupal\entity_switcher;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Interface for switcher reference lists of field items.
 */
interface SwitcherReferenceFieldItemListInterface extends FieldItemListInterface {

  /**
   * Gets the entities referenced by this field, preserving field item deltas.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  public function referencedEntities();

}
