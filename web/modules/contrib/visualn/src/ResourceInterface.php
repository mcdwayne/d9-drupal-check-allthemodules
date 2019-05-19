<?php

namespace Drupal\visualn;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * The interface is based on FieldItemInterface methods and structure.
 */
interface ResourceInterface extends ComplexDataInterface {

  /**
   * @todo: remove these methods if not needed
   */
  public function getResourceType();

  public function setResourceType($resource_type);

}
