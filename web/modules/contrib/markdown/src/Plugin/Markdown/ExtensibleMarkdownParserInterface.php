<?php

namespace Drupal\markdown\Plugin\Markdown;

/**
 * Interface MarkdownInterface.
 */
interface ExtensibleMarkdownParserInterface extends MarkdownGuidelinesAlterInterface {

  /**
   * Retrieves MarkdownExtension plugins.
   *
   * @param bool $enabled
   *   Flag indicating whether to filter results based on enabled status. By
   *   default, all extensions are returned. If set to TRUE, only enabled
   *   extensions are returned. If set to FALSE, only disabled extensions are
   *   returned.
   *
   * @return \Drupal\markdown\Plugin\Markdown\Extension\MarkdownExtensionInterface[]
   *   An array of MarkdownExtension plugins.
   */
  public function getExtensions($enabled = NULL);

}
