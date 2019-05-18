<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

/**
 * Formats multiple numbers as a list.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_number_list",
 *   label       = @Translation("Formatter Suite (deprecated) - General number list"),
 *   weight      = 10000,
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *   }
 * )
 */
class GeneralNumberListFormatter extends GeneralNumberFormatter {

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t('<span class="formatter_suite-deprecated">Deprecated. Please switch to "General number", which has all of the same features. This formatter will be deleted in a future release.</span>');
  }

}
