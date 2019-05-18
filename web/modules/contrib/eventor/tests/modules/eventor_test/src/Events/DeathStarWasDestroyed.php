<?php

namespace Drupal\eventor_test\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class DeathStarWasDestroyed.
 */
class DeathStarWasDestroyed extends Event {

  /**
   * Test parameter.
   *
   * @var bool
   */
  public $darthVaderIsHappy;

  /**
   * Test parameter.
   *
   * @var bool
   */
  public $lukeIsHappy;

}
