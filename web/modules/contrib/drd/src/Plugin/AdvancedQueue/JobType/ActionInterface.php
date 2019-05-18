<?php

namespace Drupal\drd\Plugin\AdvancedQueue\JobType;


interface ActionInterface {

  /**
   * Process the action which got prepared in advancne.
   *
   * @return array|bool
   *   The result from the action.
   */
  public function processAction();

}
