<?php

namespace Drupal\colorapi\TypedData\Definition;

use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Definition class for Typed Data API Color Complex Data types.
 */
class ColorDataDefinition extends MapDataDefinition {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset($this->propertyDefinitions)) {
      $typed_data_manager = \Drupal::typedDataManager();

      $hex_color_definition_info = $typed_data_manager->getDefinition('hexadecimal_color');
      $this->propertyDefinitions['hexadecimal'] = $hex_color_definition_info['definition_class']::create('hexadecimal_color')
        ->setLabel('Hexadecimal Color')
        ->setDescription('The color in hexadecimal string format');

      $rgb_color_definition_info = $typed_data_manager->getDefinition('rgb_color');
      $this->propertyDefinitions['rgb'] = $rgb_color_definition_info['definition_class']::create('rgb_color')
        ->setLabel('RGB Color')
        ->setDescription('The color in RGB format');
    }

    return $this->propertyDefinitions;
  }

}
