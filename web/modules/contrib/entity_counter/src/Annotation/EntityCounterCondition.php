<?php

namespace Drupal\entity_counter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the entity counter condition plugin annotation object.
 *
 * Plugin Namespace: Plugin\EntityCounterCondition.
 *
 * @see hook_entity_counter_condition_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterConditionManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class EntityCounterCondition extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The entity counter condition label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The entity counter condition category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The entity counter condition entity type ID.
   *
   * @var string
   */
  // @codingStandardsIgnoreStart
  public $entity_type;
  // @codingStandardsIgnoreEnd

  /**
   * The entity counter condition weight.
   *
   * Used when sorting the condition list in the UI.
   *
   * @var int
   */
  public $weight = 0;

}
