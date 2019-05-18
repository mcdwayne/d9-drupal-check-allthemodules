<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface MarkdownGuideInterface.
 */
interface MarkdownGuidelinesInterface {

  /**
   * Builds a guide on how to use the Markdown Parser.
   *
   * @return array
   *   The modified guides array.
   */
  public function getGuidelines();

}
