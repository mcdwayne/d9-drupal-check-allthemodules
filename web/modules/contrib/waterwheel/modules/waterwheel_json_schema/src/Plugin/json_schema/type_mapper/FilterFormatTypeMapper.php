<?php

namespace Drupal\waterwheel_json_schema\Plugin\json_schema\type_mapper;

use Drupal\waterwheel_json_schema\Plugin\TypeMapperBase;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Converts Data Definition properties of filter_format type to JSON Schema.
 *
 * @TypeMapper(
 *  id = "filter_format"
 * )
 */
class FilterFormatTypeMapper extends TypeMapperBase {

  /**
   * {@inheritdoc}
   */
  public function getMappedValue(DataDefinitionInterface $property) {
    $value = parent::getMappedValue($property);
    $value['type'] = 'string';
    return $value;
  }

}
