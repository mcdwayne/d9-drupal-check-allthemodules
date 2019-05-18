<?php

namespace Drupal\discussions_email\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DiscussionsEmailPlugin annotation object.
 *
 * Plugin Namespace: Plugin\DiscussionsEmailPlugin.
 *
 * @see \Drupal\discussions_email\Plugin\DiscussionsEmailPluginInterface
 * @see \Drupal\discussions_email\DiscussionsEmailPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DiscussionsEmailPlugin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
