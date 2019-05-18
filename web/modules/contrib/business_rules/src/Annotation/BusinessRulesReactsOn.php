<?php

namespace Drupal\business_rules\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Business rules reacts on item annotation object.
 *
 * @see \Drupal\business_rules\Plugin\BusinessRulesReactsOnManager
 * @see plugin_api
 *
 * @Annotation
 */
class BusinessRulesReactsOn extends Plugin {

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
   * A human readable description of the Reacts On Event.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The event name that will trigger the rule.
   *
   * @var string
   */
  public $eventName;

  /**
   * The group of the action to be organized in the list box.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $group;

  /**
   * The priority of the event. Bigger is evaluated first.
   *
   * @var int
   */
  public $priority;

  /**
   * If the event need a target entity.
   *
   * @var bool
   */
  public $hasTargetEntity;

  /**
   * If the event has target bundle.
   *
   * @var bool
   */
  public $hasTargetBundle;

}
