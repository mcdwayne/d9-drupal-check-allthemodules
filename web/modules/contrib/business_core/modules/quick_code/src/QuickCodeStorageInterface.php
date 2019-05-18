<?php

namespace Drupal\quick_code;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for quick_code entity storage classes.
 */
interface QuickCodeStorageInterface extends ContentEntityStorageInterface {

  /**
   * Finds all parents of a given quick_code ID.
   *
   * @param int $qid
   *   Quick code ID to retrieve parents for.
   *
   * @return \Drupal\quick_code\QuickCodeInterface[]
   *   An array of quick_code objects which are the parents of the quick_code $tid.
   */
  public function loadParents($qid);

}
