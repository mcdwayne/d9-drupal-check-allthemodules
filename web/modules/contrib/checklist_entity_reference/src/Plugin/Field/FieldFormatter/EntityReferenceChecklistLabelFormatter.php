<?php

namespace Drupal\checklist_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'entity reference label' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_checklist_label",
 *   label = @Translation("Checklist Labels"),
 *   description = @Translation("Display the label of the referenced entities."),
 *   field_types = {
 *     "entity_reference_checklist"
 *   }
 * )
 */
class EntityReferenceChecklistLabelFormatter extends EntityReferenceLabelFormatter {

}
