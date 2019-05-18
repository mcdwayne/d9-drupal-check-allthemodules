<?php

namespace Drupal\nebula_editor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * FilterTables.
 *
 * @Filter(
 *   id = "filter_tables",
 *   title = @Translation("Table Responsive Filter"),
 *   description = @Translation("Custom filter to wrap tables in .table-responsive"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterTables extends FilterBase {

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
    $replace  = "<table class='table table-bordered'";
    $new_text = str_replace("<table", $replace, $text);
    return new FilterProcessResult($new_text);
  }

}
