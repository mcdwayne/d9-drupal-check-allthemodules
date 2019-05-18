<?php

namespace Drupal\reverse_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'reverse_entity_reference_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "reverse_entity_reference_entity_view",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "reverse_entity_reference"
 *   }
 * )
 */
class ReverseEntityReferenceEntityFormatter extends DynamicEntityReferenceEntityFormatter {
}
