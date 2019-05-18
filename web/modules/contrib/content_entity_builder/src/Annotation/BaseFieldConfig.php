<?php

namespace Drupal\content_entity_builder\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a BaseFieldConfig annotation object.
 *
 * Plugin Namespace: Plugin\BaseFieldConfig.
 *
 * @see hook_tab_info_alter()
 * @see \Drupal\content_entity_builder\ConfigurableBaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\ConfigurableBaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigInterface
 * @see \Drupal\content_entity_builder\BaseFieldConfigBase
 * @see \Drupal\content_entity_builder\BaseFieldConfigManager
 * @see plugin_api
 *
 * @Annotation
 */
class BaseFieldConfig extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the BaseFieldConfig.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the BaseFieldConfig.
   *
   * This will be shown when adding or configuring this BaseFieldConfig.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * The field type of the BaseFieldConfig.
   *
   * @var string
   */
  public $field_type = '';

}
