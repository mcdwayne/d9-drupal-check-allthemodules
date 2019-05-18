<?php

namespace Drupal\commerce_product_review\Plugin\Field\FieldFormatter;

/**
 * Defines the overall rating empty text formatter interface.
 *
 * Drupal field formatters normally don't have the chance to output something
 * despite on having empty values. We circumvent this by implementing
 * hook_entity_display_build_alter() in our module file. In order to let this
 * workaround be somehow structured, we check for field formatters implementing
 * this interface and call its getEmptyText() function.
 */
interface OverallRatingEmptyTextFormatterInterface {

  /**
   * Returns the text that should be displayed, if the item is empty.
   *
   * @return string
   *   The empty text.
   */
  public function getEmptyText();

}
