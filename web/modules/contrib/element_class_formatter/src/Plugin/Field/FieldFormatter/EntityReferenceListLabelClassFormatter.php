<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'file with class' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_list_label_class",
 *   label = @Translation("Label list (with class)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceListLabelClassFormatter extends EntityReferenceLabelFormatter {

  use ElementListClassTrait;

}
