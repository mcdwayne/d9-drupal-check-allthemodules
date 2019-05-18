<?php

namespace Drupal\content_synchronizer\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an EntityProcessor annotation object.
 *
 * @Annotation
 *
 * @ingroup content_synchronizer
 */
class EntityProcessor extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity entityType.
   *
   * @var string
   */
  public $entityType;

}
