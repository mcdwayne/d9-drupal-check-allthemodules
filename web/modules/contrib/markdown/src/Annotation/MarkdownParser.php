<?php

namespace Drupal\markdown\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class MarkdownParser.
 *
 * @Annotation
 *
 * @Attributes({
 *    @Attribute("id", required = true, type = "string"),
 *    @Attribute("checkClass", required = true, type = "string"),
 * })
 */
class MarkdownParser extends Plugin {

  /**
   * The parser identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The class to check if the parser is available.
   *
   * @var string
   */
  protected $checkClass;

  /**
   * The human-readable label.
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  protected $label;

}
