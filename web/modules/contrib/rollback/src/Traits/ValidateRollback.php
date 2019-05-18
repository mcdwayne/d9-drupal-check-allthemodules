<?php

namespace Drupal\rollback\Traits;

/**
 * Trait ValidateRollback.
 *
 * A trait used to define a RollableUpdate should
 * also validate the result of the down function. This is
 * done by calling the validate function and expecting false.
 */
trait ValidateRollback {

  /**
   * Validate the update has completed successfully.
   *
   * Called after the 'up' function.
   *
   * @return bool
   *   True if the validation is succcessful, false if not.
   */
  abstract public function validate();

}
