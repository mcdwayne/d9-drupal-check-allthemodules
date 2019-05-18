<?php

namespace Drupal\field_union\TypedData;

use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Defines a class for a field proxy data definition.
 */
class FieldProxyDataDefinition extends MapDataDefinition {

  /**
   * Sets proxy field type.
   *
   * @param mixed $field_type
   *   Value for field type.
   *
   * @return $this
   */
  public function setProxyFieldType($field_type) {
    $this->definition['proxy_field_type'] = $field_type;
    return $this;
  }

  /**
   * Gets proxy field type.
   *
   * @return string
   *   Property name.
   */
  public function getProxyFieldType() {
    return $this->definition['proxy_field_type'];
  }

}
