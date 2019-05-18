<?php

namespace Drupal\formatter_suite\Plugin\Field\FieldFormatter;

/**
 * Formats multiple email addresses as a list.
 *
 * See the EntityListTrait for a description of list formatting features.
 *
 * @ingroup formatter_suite
 *
 * @FieldFormatter(
 *   id          = "formatter_suite_general_email_list",
 *   label       = @Translation("Formatter Suite (deprecated) - General Email address list"),
 *   weight      = 10000,
 *   field_types = {
 *     "email",
 *   }
 * )
 */
class GeneralEmailListFormatter extends GeneralEmailFormatter {

  /**
   * Returns a brief description of the formatter.
   *
   * @return string
   *   Returns a brief translated description of the formatter.
   */
  protected function getDescription() {
    return $this->t('<span class="formatter_suite-deprecated">Deprecated. Please switch to "General Email address", which has all of the same features. This formatter will be deleted in a future release.</span>');
  }

}
