<?php

namespace Drupal\json_ld_schema\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation for JSON LD source plugins.
 *
 * @Annotation
 */
class JsonLdEntity extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label.
   *
   * @var string
   */
  public $label;

}
