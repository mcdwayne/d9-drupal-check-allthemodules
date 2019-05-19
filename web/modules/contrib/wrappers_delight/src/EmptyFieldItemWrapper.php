<?php

namespace Drupal\wrappers_delight;


class EmptyFieldItemWrapper extends FieldItemWrapper {

  /**
   * @inheritDoc
   */
  public function __construct() {
    
  }

  /**
   * @inheritDoc
   */
  public function raw() {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function view($display_options) {
    return [];
  }

  /**
   * @inheritDoc
   */
  public function isEmpty() {
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function getValue() {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function getString() {
    return '';
  }

  /**
   * @inheritDoc
   */
  public function get($property_name) {
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function set($property_name, $value) {
    return $this;
  }


}
