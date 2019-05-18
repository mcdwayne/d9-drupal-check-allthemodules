<?php

namespace Drupal\entity_list\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Entity list display item annotation object.
 *
 * @see \Drupal\entity_list\Plugin\EntityListDisplayManager
 * @see plugin_api
 *
 * @Annotation
 */
class EntityListDisplay extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
