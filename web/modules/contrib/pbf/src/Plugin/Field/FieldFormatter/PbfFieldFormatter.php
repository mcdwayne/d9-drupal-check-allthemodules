<?php

namespace Drupal\pbf\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'pbf_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "pbf_formatter_default",
 *   label = @Translation("Permissions by field default formatter"),
 *   field_types = {
 *     "pbf"
 *   }
 * )
 */
class PbfFieldFormatter extends EntityReferenceLabelFormatter {

}
