<?php

namespace Drupal\paragraphs_trimmed\Plugin;

/**
 * Defines an interface for typed data manager.
 */
interface ParagraphsTrimmedFormatterInterface {

  /**
   * Get the plugin id of the formatter that will be used to render the
   * final rendered paragraphs output.
   *
   * @return string
   *   The formatter plugin id to use for trimming paragraphs output.
   */
  public static function getTrimFormatterType();

}
