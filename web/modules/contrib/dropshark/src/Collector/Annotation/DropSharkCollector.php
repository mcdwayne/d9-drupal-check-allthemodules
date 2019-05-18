<?php

namespace Drupal\dropshark\Collector\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Class DropSharkCollector.
 *
 * @Annotation
 */
class DropSharkCollector extends Plugin {

  /**
   * The collector plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the collector plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The description of the collector plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Events which the collector responds to.
   *
   * @var array
   */
  public $events;

}
