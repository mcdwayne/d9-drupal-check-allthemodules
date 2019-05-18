<?php

namespace Drupal\flexiform;

use Drupal\Core\Entity\Display\EntityFormDisplayInterface;

/**
 * Extends the EntityFormDisplay interface to work with multiple entities.
 */
interface FlexiformEntityFormDisplayInterface extends EntityFormDisplayInterface {

  /**
   * Get the Flexiform form Entity Configuration from the object.
   *
   * @return array
   *   The form entity configuration.
   */
  public function getFormEntityConfig();

}
