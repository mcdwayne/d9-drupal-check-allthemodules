<?php

namespace Drupal\entity_reference_layout\Plugin\Field\FieldFormatter;

use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Entity Reference with Layout field formatter.
 *
 * Currently stub only. Content is formatted in
 * module theme functions.
 *
 * @todo: Move formatter functionality out
 *  of module into field formatter class.
 *
 * @FieldFormatter(
 *   id = "entity_reference_layout",
 *   label = @Translation("Entity reference layout"),
 *   description = @Translation("Display the referenced entities recursively rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference_layout",
 *     "entity_reference_layout_revisioned"
 *   }
 * )
 */
class EntityReferenceLayoutFormatter extends EntityReferenceRevisionsEntityFormatter {
}
