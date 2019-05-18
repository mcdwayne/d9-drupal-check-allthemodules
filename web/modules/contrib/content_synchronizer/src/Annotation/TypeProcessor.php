<?php

namespace Drupal\content_synchronizer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an TypeProcessor annotation object.
 *
 * @Annotation
 *
 * @ingroup content_synchronizer
 */
class TypeProcessor extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity fieldType.
   *
   * @var string
   */
  public $fieldType;

}
