<?php

namespace Drupal\reverse_entity_reference\Plugin\Field\FieldType;

use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceItem;

/**
 * Plugin implementation of the 'reverse_entity_reference' field type.
 *
 * @FieldType(
 *   id = "reverse_entity_reference",
 *   label = @Translation("Reverse Entity Reference"),
 *   description = @Translation("An entity field containing a reverse entity reference"),
 *   category = @Translation("Reverse Reference"),
 *   list_class = "\Drupal\reverse_entity_reference\Plugin\Field\FieldType\ReverseReferenceList",
 *   default_formatter = "reverse_entity_reference_label",
 *   no_ui = TRUE
 * )
 */
class ReverseEntityReferenceItem extends DynamicEntityReferenceItem {
}
