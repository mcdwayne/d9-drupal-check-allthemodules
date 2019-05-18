<?php

/**
 * @file
 * Contains \Drupal\entity_reference_inline\Plugin\Field\FieldFormatter\EntityReferenceInlineEntityFormatter.
 */

namespace Drupal\entity_reference_inline\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_inline_entity_view",
 *   label = @Translation("Rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view()."),
 *   field_types = {
 *     "entity_reference_inline"
 *   }
 * )
 */
class EntityReferenceInlineEntityFormatter extends EntityReferenceEntityFormatter {

  use FieldFormatterCommonMethodsTrait;

}
