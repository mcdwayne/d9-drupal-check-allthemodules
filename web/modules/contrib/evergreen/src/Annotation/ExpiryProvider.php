<?php

namespace Drupal\evergreen\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an evergreen expiry provider plugin annotation object.
 *
 * Plugin Namespace: Plugin\ExpiryProvider
 *
 * @Annotation
 */
class ExpiryProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the expiry provider.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the expiry provider.
   *
   * This will be shown when adding or configuring this image effect.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
