<?php

namespace Drupal\entity_embed_extras\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Dialog Entity Display Plugin annotation object.
 *
 * Plugin Namespace: Plugin\entity_embed\DialogEntityDisplay.
 *
 * For a working example, see
 * \Drupal\entity_embed\Plugin\entity_embed\DialogEntityDisplay\Label
 *
 * @see hook_entity_embed_dialog_entity_display_info()
 * @see \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayBase
 * @see \Drupal\entity_embed\DialogEntityDisplay\DialogEntityDisplayManager
 * @see \Drupal\entity_embed\Annotation\DialogEntityDisplay
 * @see plugin_api
 *
 * @Annotation
 */
class DialogEntityDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translationoptional
   *
   * This will be shown when adding or configuring this plugin.
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
