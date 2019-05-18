<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

/**
 * Interface CommonMarkExtensionInterface.
 */
interface CommonMarkRendererInterface {

  /**
   * Retrieves the AST class used to parse the document for the renderer.
   *
   * @return string
   *   A fully qualified class name or a single element name that will be
   *   prefixed with "League\CommonMark\(Block|Inline)\Element".
   */
  public function rendererClass();

}
