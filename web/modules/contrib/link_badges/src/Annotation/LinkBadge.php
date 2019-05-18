<?php

namespace Drupal\link_badges\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a LinkBadge annotation object.
 *
 * Plugin Namespace: Plugin\LinkBadge
 *
 * @see plugin_api
 *
 * @Annotation
 */
class LinkBadge extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the link badge plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
