<?php

namespace Drupal\advanced_update;

/**
 * AdvancedUpdateInterface.
 *
 * Define all functions needed to perform an Advanced update.
 */
interface AdvancedUpdateInterface {

  /**
   * Applying an update for the module containing this class.
   *
   * @return bool
   *      TRUE if the update has worked.
   */
  public function up();

  /**
   * Applying the reverse of the update for the module containing this class.
   *
   * @return bool
   *    TRUE if the update has worked.
   */
  public function down();

}
