<?php

namespace Drupal\bundle_override\Manager\Objects;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an TypeProcessor annotation object.
 *
 * @Annotation
 *
 * @ingroup bundle_override
 */
class BundleOverrideObjects extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
