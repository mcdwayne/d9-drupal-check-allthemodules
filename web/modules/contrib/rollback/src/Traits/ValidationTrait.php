<?php

namespace Drupal\rollback\Traits;

/**
 * Trait ValidationTrait.
 */
trait ValidationTrait {

  /**
   * Validate the update has completed successfully.
   *
   * Called after the 'up' function.
   *
   * In theory, this function can also be called after a rollback
   * to determine if the rollback was also executed successfully,
   * by returning 'false'.
   *
   * @return bool
   *   True if the validation is succcessful, false if not.
   */
  abstract public function validate();

}
