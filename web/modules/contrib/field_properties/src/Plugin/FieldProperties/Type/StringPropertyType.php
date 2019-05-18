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
 *   id = "string",
 *   label = @Translation("String"),
 *   weight = -50,
 * )
 */
class StringPropertyType extends PluginBase implements FieldPropertyTypeInterface {

}
