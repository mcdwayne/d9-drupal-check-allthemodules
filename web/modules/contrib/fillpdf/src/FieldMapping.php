<?php

namespace Drupal\fillpdf;

/**
 * Represents a mapping between a PDF field and a merge value (a value with
 * which to fill in the field). This is a barebones base class intended to be
 * subclassed and enhanced with additional properties and getter methods.
 *
 * FieldMapping objects are immutable; replace the value by calling the
 * constructor again if the value needs to change.
 */
abstract class FieldMapping {

  /**
   * @var mixed
   *
   * The primary value of the mapping.
   */
  protected $data;

  public function __construct($data) {
    $this->data = $data;
  }

  public function getData() {
    return $this->data;
  }

}
