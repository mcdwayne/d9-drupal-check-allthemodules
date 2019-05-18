<?php

namespace Drupal\required_api\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an field required api annotation object.
 *
 * @see hook_required_api_info_alter()
 *
 * @Annotation
 */
class Required extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the api.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the api.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
