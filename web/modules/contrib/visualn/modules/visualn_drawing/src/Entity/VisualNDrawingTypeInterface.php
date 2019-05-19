<?php

namespace Drupal\visualn_drawing\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface for defining VisualN Drawing type entities.
 */
interface VisualNDrawingTypeInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

  /**
   * Get drawing fetcher field machine name default value for the entity type
   *
   * @todo: maybe rename the method
   */
  public function getDrawingFetcherField();

}
