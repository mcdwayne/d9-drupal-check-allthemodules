<?php

namespace Drupal\rules_webform;

use Drupal\Core\TypedData\MapDataDefinition;

/**
 * A typed data definition class for defining 'webform_fields'.
 */
class WebformFieldsDataDefinition extends MapDataDefinition {

  /**
   * Creates a new 'webform_field' definition.
   *
   * @param string $type
   *   (optional) The data type of the map. Defaults to 'webform_field'.
   *
   * @return static
   */
  public static function create($type = 'webform_fields') {
    $definition['type'] = $type;
    $webform_fields_definition = new static($definition);

    // Array of a webform fields names and titles which has the following structure:
    // ['field 1 name' => 'field 1 title', 'field 2 name' => 'field 2 title'].
    $fieldsDefinitions = \Drupal::state()->get('rules_webform.fields_definitions');

    foreach ($fieldsDefinitions as $name => $title) {
      $property_definition = \Drupal::typedDataManager()->createDataDefinition('string');
      $property_definition->setLabel($title);
      $webform_fields_definition->setPropertyDefinition($name, $property_definition);
    }

    return $webform_fields_definition;
  }

}
