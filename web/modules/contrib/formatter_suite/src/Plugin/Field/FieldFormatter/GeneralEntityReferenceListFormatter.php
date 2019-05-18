<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

/**
 * Formats multiple entity references as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @deprecated List features have been added to GeneralEntityReferenceFormatter.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_entity_reference_list",
 *   label       = @Translation("Formatter Suite (deprecated) - General entity reference list"),
 *   weight      = 10000,
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class GeneralEntityReferenceListFormatter extends GeneralEntityReferenceFormatter {

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t('<span class="formatter_suite-deprecated">Deprecated. Please switch to "General entity reference", which has all of the same features. This formatter will be deleted in a future release.</span>');
  }

}
