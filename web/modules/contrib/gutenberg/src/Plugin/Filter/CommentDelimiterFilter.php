<?php

namespace Drupal\gutenberg\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "filter_comment_delimiter",
 *   title = @Translation("Gutenberg comment delimiter filter"),
 *   description = @Translation("Cleans comments delimiters from content."),
 *   settings = {
 *   },
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class CommentDelimiterFilter extends FilterBase {

  /**
   * Process each delimiter.
   */
  public function process($text, $langcode) {

    $lines = explode("\n", $text);

    $lines = preg_replace_callback('#<!-- \/?wp:.* \/?-->#', [$this, 'renderContent'], $lines);

    $text = implode("\n", $lines);

    return new FilterProcessResult($text);
  }

  /**
   * Callback function to process each delimiter.
   */
  private function renderContent($match) {
    return '';
  }
}
