<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface MarkdownGuideInterface.
 */
interface MarkdownGuidelinesAlterInterface {

  /**
   * Alters existing guides on how to use the Markdown Parser.
   *
   * @param array $guides
   *   The guides array, passed by reference.
   */
  public function alterGuidelines(array &$guides = []);

}
