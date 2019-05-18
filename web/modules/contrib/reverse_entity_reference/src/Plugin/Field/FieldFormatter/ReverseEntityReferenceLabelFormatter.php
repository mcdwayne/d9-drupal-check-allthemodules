<?php

namespace Drupal\reverse_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'reverse entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "reverse_entity_reference_label",
 *   label = @Translation("Label"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "reverse_entity_reference"
 *   }
 * )
 */
class ReverseEntityReferenceLabelFormatter extends DynamicEntityReferenceLabelFormatter {
}
