<?php

namespace Drupal\dea\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class SolutionDiscovery extends Plugin {
  /**
   * The plugin id.
   * @var string $id
   */
  public $id;

  /**
   * The plugin label.
   * @var \Drupal\Core\Annotation\Translation $label
   */
  public $label;
}