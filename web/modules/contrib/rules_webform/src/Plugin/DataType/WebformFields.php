<?php

namespace Drupal\rules_webform\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * The "webform_fields" data type.
 *
 * @ingroup typed_data
 *
 * @DataType(
 *   id = "webform_fields",
 *   label = @Translation("Webform Fields"),
 *   definition_class = "Drupal\rules_webform\WebformFieldsDataDefinition"
 * )
 */
class WebformFields extends Map {
}
