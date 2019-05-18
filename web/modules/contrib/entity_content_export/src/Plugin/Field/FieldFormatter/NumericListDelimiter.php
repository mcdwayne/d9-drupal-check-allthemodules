<?php

namespace Drupal\entity_content_export\Plugin\Field\FieldFormatter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldFormatter;

/**
 * Define list delimiter.
 *
 * @FieldFormatter(
 *   id = "entity_content_export_numeric_list_delimiter",
 *   label = @Translation("List delimiter"),
 *   field_types = {"integer", "decimal", "float"}
 * )
 */
class NumericListDelimiter extends TextListDelimiter {

}
