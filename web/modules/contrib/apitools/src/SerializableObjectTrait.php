<?php

namespace Drupal\apitools;

trait SerializableObjectTrait {

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->getFields());
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return (object) $this->toArray();
  }

  /**
   * Convert object to array for serialization or api response.
   */
  public function toArray() {
    $array = ['id' => $this->id];
    foreach ($this as $field) {
      $array[$field] = $this->get($field);
    }
    return $array;
  }
}
