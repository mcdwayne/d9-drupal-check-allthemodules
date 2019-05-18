<?php

namespace Drupal\developer_suite_examples\Collection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\developer_suite\Collection;

/**
 * Class ExampleGenericCollection.
 *
 * Extend the \Drupal\developer_suite\Collection class and pass the $entityType
 * and the $entityTypeManager parameters into the parent constructor.
 *
 * @package Drupal\developer_suite_examples\Collection
 */
class ExampleGenericCollection extends Collection {

  /**
   * ExampleGenericCollection constructor.
   *
   * When injecting your own services please make sure that you pass the
   * $entityType and $entityTypeManager parameters into the parent constructor.
   *
   * A generic collection can be used to store all sorts of data in a structured
   * and uniform way. Add items to a collection via the add() and/or
   * addMultiple() methods. Retrieving of the data is done via the get() and/or
   * getItems() methods.
   *
   * @param string $entityType
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct($entityType, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entityType, $entityTypeManager);
    // Set your own injected services here.
  }

}

