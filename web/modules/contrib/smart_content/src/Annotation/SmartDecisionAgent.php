<?php

namespace Drupal\smart_content\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Smart decision agent item annotation object.
 *
 * @see \Drupal\smart_content\DecisionAgent\DecisionAgentManager
 * @see plugin_api
 *
 * @Annotation
 */
class SmartDecisionAgent extends Plugin {

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

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The placeholder attribute ID.
   *
   * @var string
   */
  public $placeholder_attribute;

}
