<?php

namespace Drupal\webform_scheduled_tasks\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for a task plugin.
 *
 * @Annotation
 */
class WebformScheduledTask extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable label.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
