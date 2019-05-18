<?php

namespace Drupal\contentserialize;

/**
 * Provides an interace defining a content importer.
 */
interface ImporterInterface {

  /**
   * Imports entities from their serialized representation.
   *
   * @param \Traversable|\Drupal\contentserialize\SerializedEntity[] $items
   *   An array or generator of SerializedEntity objects keyed by UUID.
   *
   * @return \Drupal\contentserialize\Result
   */
  public function import($items);

}
