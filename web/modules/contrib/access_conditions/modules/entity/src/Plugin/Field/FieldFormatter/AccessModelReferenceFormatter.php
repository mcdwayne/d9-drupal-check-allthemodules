<?php

namespace Drupal\access_conditions_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Implementation of the 'access_model_reference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "access_model_reference_formatter",
 *   label = @Translation("Access conditions"),
 *   field_types = {
 *     "access_model_reference"
 *   }
 * )
 */
class AccessModelReferenceFormatter extends EntityReferenceLabelFormatter {

}
