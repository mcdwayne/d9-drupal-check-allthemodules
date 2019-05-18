<?php

namespace Drupal\patchinfo\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines PatchInfo Source plugin annotation.
 *
 * @Annotation
 */
class PatchInfoSource extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
