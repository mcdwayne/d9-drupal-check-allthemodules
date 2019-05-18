<?php

namespace Drupal\entity_counter\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\entity_counter\EntityCounterSourceCardinality;
use Drupal\entity_counter\EntityCounterSourceValue;

/**
 * Defines an entity counter source annotation object.
 *
 * Plugin Namespace: Plugin\EntityCounterSource.
 *
 * For a working example, see
 * \Drupal\entity_counter\Plugin\EntityCounterSource\ManualTransaction
 *
 * @see hook_entity_counter_source_info_alter()
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceInterface
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceBase
 * @see \Drupal\entity_counter\Plugin\EntityCounterSourceManagerInterface
 * @see plugin_api
 *
 * @Annotation
 */
class EntityCounterSource extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the entity counter source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the entity counter source.
   *
   * This will be shown when adding or configuring this entity counter source.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The maximum number of instances allowed for this entity counter source.
   *
   * Possible values are positive integers or
   * \Drupal\entity_counter\EntityCounterSourceCardinality::UNLIMITED
   * \Drupal\entity_counter\EntityCounterSourceCardinality::SINGLE.
   *
   * @var int
   */
  public $cardinality = EntityCounterSourceCardinality::UNLIMITED;

  /**
   * Determines if the plugin obtains an incremental or absolute value to add.
   *
   * Possible values are:
   * \Drupal\entity_counter\EntityCounterSourceValue::ABSOLUTE
   * \Drupal\entity_counter\EntityCounterSourceValue::INCREMENTAL.
   *
   * @var string
   */
  // @codingStandardsIgnoreStart
  public $value_type = EntityCounterSourceValue::ABSOLUTE;
  // @codingStandardsIgnoreEnd

}
