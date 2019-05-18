<?php

namespace Drupal\rules_webform\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * The "webform_info" data type.
 *
 * @ingroup typed_data
 *
 * @DataType(
 *   id = "webform_info",
 *   label = @Translation("Webform info"),
 *   definition_class = "Drupal\rules_webform\WebformInfoDataDefinition"
 * )
 */
class WebformInfo extends Map {
}
