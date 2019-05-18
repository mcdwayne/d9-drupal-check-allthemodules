<?php

namespace Drupal\apitools;

interface SerializableObjectInterface extends \JsonSerializable, \IteratorAggregate {

  /**
   * An array of field values required to be converted to an array or JSON.
   *
   * @return array
   */
  public function getFields();
}
