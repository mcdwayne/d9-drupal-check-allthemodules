<?php

namespace Drupal\drd\Plugin\Action;

/**
 * Interface for global actions that can be executed locally.
 */
interface BaseGlobalInterface extends BaseInterface {

  /**
   * Execute the global action.
   *
   * @return bool
   *   TRUE if action execution succeeded.
   */
  public function executeAction();

}
