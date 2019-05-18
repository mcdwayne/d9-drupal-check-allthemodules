<?php

namespace Drupal\rewrite_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Transform Case Class.
 *
 * @Annotation
 */
class TransformCase extends Plugin {

  public $id;
  public $title = "";
  public $description = "";

}
