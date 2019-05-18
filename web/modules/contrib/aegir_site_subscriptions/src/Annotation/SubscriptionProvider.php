<?php

namespace Drupal\aegir_site_subscriptions\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Aegir site subscription provider item annotation object.
 *
 * @see \Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class SubscriptionProvider extends Plugin {

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

}
