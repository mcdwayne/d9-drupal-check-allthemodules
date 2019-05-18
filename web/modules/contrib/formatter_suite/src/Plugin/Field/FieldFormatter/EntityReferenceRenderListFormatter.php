<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Formats multiple entity reference rendered entities as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_entity_reference_render_list",
 *   label       = @Translation("Formatter Suite - Rendered entity list"),
 *   weight      = 1002,
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EntityReferenceRenderListFormatter extends EntityReferenceEntityFormatter {
  use EntityListTrait;

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t('Format multi-value entity reference fields as a list of rendered entities.');
  }

}
