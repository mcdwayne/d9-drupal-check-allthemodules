<?php

namespace Drupal\developer_suite_examples\Collection;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\developer_suite\Collection;

/**
 * Class ExampleFileCollection.
 *
 * Extend the \Drupal\developer_suite\Collection class and pass the $entityType
 * and the $entityTypeManager parameters into the parent constructor.
 *
 * @package Drupal\developer_suite_examples\Collection
 */
class ExampleFileCollection extends Collection {

  /**
   * ExampleFileCollection constructor.
   *
   * When injecting your own services please make sure that you pass the
   * $entityType and $entityTypeManager parameters into the parent constructor.
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

  /**
   * Loads all files.
   *
   * @return $this|bool
   *   The ExampleFileCollection or FALSE.
   */
  public function loadAllFilesExample() {
    // Access the entity query via the parent $entityQuery property.
    $query = $this->entityQuery;
    // Set your conditions.
    $query->condition('type', 'file');
    // Execute the query.
    $result = $query->execute();

    // Pass the entity IDs into the load() method. The load() method loads your
    // queried entities into this class and returns itself.
    return $this->load($result);
  }

}

