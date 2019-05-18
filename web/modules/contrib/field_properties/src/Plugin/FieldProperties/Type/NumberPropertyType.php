<?php

/**
 * @file
 * Definition of Drupal\field_properties\Plugin\Field\FieldPropertiesFormatter.
 */

namespace Drupal\field_properties\Plugin\FieldProperties\Type;

use Drupal\Core\Plugin\PluginBase;
use Drupal\field_properties\Plugin\FieldPropertyTypeInterface;

/**
 * Plugin implementation of the 'field_properties_formatter' formatter.
 *
 * @Plugin(
 *   id = "number",
 *   label = @Translation("Number"),
 *   weight = -25,
 * )
 */
class NumberPropertyType extends PluginBase implements FieldPropertyTypeInterface {

}
