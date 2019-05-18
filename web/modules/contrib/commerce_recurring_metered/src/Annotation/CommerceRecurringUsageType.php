<?php

namespace Drupal\commerce_recurring_metered\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the usage type plugin annotation object.
 *
 * Plugin namespace: Plugin\Commerce\UsageType.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceRecurringUsageType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The usage type label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
