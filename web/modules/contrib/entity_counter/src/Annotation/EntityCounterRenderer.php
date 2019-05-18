<?php

namespace Drupal\entity_counter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity counter renderer annotation object.
 *
 * Plugin Namespace: Plugin\EntityCounterRenderer.
 *
 * For a working example, see
 * \Drupal\entity_counter\Plugin\EntityCounterRenderer\Plain
 *
 * @see hook_entity_counter_renderer_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterRendererManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class EntityCounterRenderer extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the entity counter renderer.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the entity counter renderer.
   *
   * This will be shown when adding or configuring this entity counter renderer.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
