<?php

namespace Drupal\flashpoint_lrs_client\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a FlashpointLRSClient annotation object.
 *
 * Plugin Namespace: Plugin\flashpoint_lrs_client
 *
 * @see plugin_api
 *
 * @Annotation
 */
class FlashpointLRSClient extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the FlashpointLRSClient.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The category under which the FlashpointLRSClient should be listed in the UI.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

}