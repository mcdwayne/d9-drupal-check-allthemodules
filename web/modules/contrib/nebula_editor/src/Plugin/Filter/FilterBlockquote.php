<?php

namespace Drupal\nebula_editor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * FilterBlockquote.
 *
 * @Filter(
 *   id = "filter_blockquote",
 *   title = @Translation("Blockquote Responsive Filter"),
 *   description = @Translation("Custom filter for blockquotes"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterBlockquote extends FilterBase {

  /**
   * Process.
   *
   * @param string $text
   *   The text.
   * @param string $langcode
   *    The langcode.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   Filtered result.
   */
  public function process($text, $langcode) {

    // if we already have a class....
    $replace  = "<blockquote class='blockquote'>";
    $new_text = str_replace("<blockquote>", $replace, $text);
    return new FilterProcessResult($new_text);
  }

}
