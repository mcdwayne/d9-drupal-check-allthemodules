<?php

namespace Drupal\bundle_override\Manager\EntityTypes;

use Drupal\Component\Annotation\Plugin;

/**
 * Define an TypeProcessor annotation object.
 *
 * @Annotation
 *
 * @ingroup bundle_override
 */
class BundleOverrideEntityTypes extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

}
