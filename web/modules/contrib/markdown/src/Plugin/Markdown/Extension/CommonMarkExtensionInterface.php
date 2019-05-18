<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

/**
 * Interface CommonMarkExtensionInterface.
 */
interface CommonMarkExtensionInterface extends MarkdownExtensionInterface {

  /**
   * Retrieves the name of the extension.
   *
   * @return string
   *   The name of the extension.
   */
  public function getName();

}
