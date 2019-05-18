<?php

namespace Drupal\entity_usage\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity_usage track annotation object.
 *
 * @see hook_entity_usage_track_info_alter()
 *
 * @Annotation
 */
class EntityUsageTrack extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the tracking method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the tracking method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
