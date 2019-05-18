<?php

declare(strict_types = 1);

namespace Drupal\erg;

/**
 * Defines events.
 */
final class Event {

  /**
   * Dispatched right before a referent will be deleted.
   */
  public const PRE_REFERENT_DELETE = 'pre_referent_delete';

  /**
   * Dispatched when validating a referee.
   */
  public const REFEREE_VALIDATE = 'referee_validate';

}
