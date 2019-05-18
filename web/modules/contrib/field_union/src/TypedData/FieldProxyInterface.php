<?php

namespace Drupal\field_union\TypedData;

use Drupal\Core\TypedData\ComplexDataInterface;

/**
 * Defines an interface for a field proxy.
 */
interface FieldProxyInterface extends \IteratorAggregate, ComplexDataInterface {

  /**
   * Act on the field being saved.
   */
  public function preSave();

}
