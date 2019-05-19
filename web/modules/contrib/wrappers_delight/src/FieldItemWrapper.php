<?php

namespace Drupal\wrappers_delight;

use Drupal\Core\Field\FieldItemInterface;

class FieldItemWrapper {

  /**
   * @var \Drupal\Core\Field\FieldItemInterface
   */
  protected $item;

  /**
   * FieldItemWrapper constructor.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   */
  protected function __construct(FieldItemInterface $item) {
    $this->item = $item;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *
   * @return static
   */
  public static function wrap(FieldItemInterface $item) {
    return new static($item);
  }

  /**
   * @return \Drupal\Core\Field\FieldItemInterface
   */
  public function raw() {
    return $this->item;
  }

  /**
   * @param array $display_options
   *
   * @return array
   */
  public function view($display_options) {
    return $this->item->view($display_options);
  }

  /**
   * @return bool
   */
  public function isEmpty() {
    return $this->item->isEmpty();
  }
  
  /**
   * @return string
   */
  public function getValue() {
    return $this->item->getValue()[$this->item->mainPropertyName()];
  }

  /**
   * @return string
   */
  public function getString() {
    return $this->item->getString();
  }

  /**
   * @param string $property_name
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   */
  public function get($property_name) {
    return $this->item->get($property_name);
  }

  /**
   * @param string $property_name
   * @param mixed $value
   *
   * @return $this
   */
  public function set($property_name, $value) {
    $this->item->set($property_name, $value, $notify = TRUE);
    return $this;
  }

}
