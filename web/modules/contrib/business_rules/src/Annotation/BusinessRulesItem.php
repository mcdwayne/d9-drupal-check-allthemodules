<?php

namespace Drupal\business_rules\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Business rules item base annotation object.
 *
 * @see plugin_api
 *
 * @Annotation
 */
abstract class BusinessRulesItem extends Plugin {

  /**
   * A human readable description of the item.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The group of the item to be organized in the list box.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $group;

  /**
   * If the item has target bundle.
   *
   * @var bool
   */
  public $hasTargetBundle = FALSE;

  /**
   * If the item need a target entity.
   *
   * @var bool
   */
  public $hasTargetEntity = FALSE;

  /**
   * If the condition has target field.
   *
   * If true, you must need to add the field property on item schema file.
   *
   * @var bool
   */
  public $hasTargetField = FALSE;

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * If the item depends on context.
   *
   * @var bool
   */
  public $isContextDependent = FALSE;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The reactsOn ids applicable for the item.
   *
   * Leave empty if it's applicable for all reactsOn events.
   *
   * @var array
   */
  public $reactsOnIds = [];

}
