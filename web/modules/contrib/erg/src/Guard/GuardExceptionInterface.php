<?php

declare(strict_types = 1);

namespace Drupal\erg\Guard;

use Drupal\erg\EntityReference;

/**
 * Defines an entity reference guard exception.
 */
interface GuardExceptionInterface extends \Throwable {

  /**
   * Gets the entity reference the exception is thrown for.
   *
   * @return \Drupal\erg\EntityReference
   *   The entity reference.
   */
  public function getEntityReference(): EntityReference;

}
