<?php

namespace Drupal\entity_type_class\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class EntityTypeClass.
 *
 * @package Drupal\entity_type_class\Annotation
 *
 * @Annotation
 */
class EntityTypeClass extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The entity type.
   *
   * @var string
   */
  public $entity;

}
