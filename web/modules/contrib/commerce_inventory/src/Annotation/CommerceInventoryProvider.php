<?php

namespace Drupal\commerce_inventory\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Inventory Provider item annotation object.
 *
 * @see \Drupal\commerce_inventory\InventoryProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class CommerceInventoryProvider extends Plugin {

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
   * The category of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context = [];

  /**
   * The administrative description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * Inventory Item has additional configuration that is required.
   *
   * @var bool
   */
  public $item_configuration_required = FALSE;

  /**
   * Inventory Item requires a remote id.
   *
   * @var bool
   */
  public $item_remote_id_required = FALSE;

  /**
   * Inventory Location requires a remote id.
   *
   * @var bool
   */
  public $location_remote_id_required = FALSE;

}
