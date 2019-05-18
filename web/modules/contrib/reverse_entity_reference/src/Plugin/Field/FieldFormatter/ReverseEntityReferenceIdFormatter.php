<?php

namespace Drupal\reverse_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceIdFormatter;

/**
 * Plugin implementation of the 'reverse entity reference ID' formatter.
 *
 * @FieldFormatter(
 *   id = "reverse_entity_reference_entity_id",
 *   label = @Translation("Entity ID"),
 *   description = @Translation("Display the ID of the referenced entities."),
 *   field_types = {
 *     "reverse_entity_reference"
 *   }
 * )
 */
class ReverseEntityReferenceIdFormatter extends DynamicEntityReferenceIdFormatter {
}
