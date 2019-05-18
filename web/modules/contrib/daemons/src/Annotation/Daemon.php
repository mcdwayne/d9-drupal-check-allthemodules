<?php

namespace Drupal\daemons\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a daemon annotation object.
 *
 * @Annotation
 */
class Daemon extends Plugin {

  /**
   * The daemon plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the daemon.
   *
   * @var string
   */
  public $label;

  /**
   * The timer with which the daemon runs (in micro seconds).
   *
   * @var string
   */
  public $periodicTimer;

}
