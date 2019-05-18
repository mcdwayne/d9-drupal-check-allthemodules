<?php

namespace Drupal\markdown\Annotation;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Drupal\Component\Annotation\Plugin;

/**
 * Class MarkdownExtension.
 *
 * @Annotation
 *
 * @Attributes({
 *   @Attribute("id", type = "string", required = true),
 *   @Attribute("parser", type = "string", required = true),
 * })
 */
class MarkdownExtension extends Plugin {

  /**
   * The parser identifier.
   *
   * @var string
   */
  protected $id;

  /**
   * The id of a MarkdownParser annotated plugin this extension belongs to.
   *
   * @var string
   */
  protected $parser;

  /**
   * The class to check if the extension is available.
   *
   * @var string
   */
  protected $checkClass;

  /**
   * The composer vendor/name that contains the extension.
   *
   * @var string
   */
  protected $composer;

  /**
   * The homepage of the extension.
   *
   * @var string
   */
  protected $homepage;

  /**
   * The human-readable label.
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * The description of the extension.
   *
   * @var string|\Drupal\Core\Annotation\Translation
   */
  protected $description;

}
