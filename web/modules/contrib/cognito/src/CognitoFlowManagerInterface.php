<?php

namespace Drupal\cognito;

/**
 * The plugin manager for different cognito flows.
 */
interface CognitoFlowManagerInterface {

  /**
   * Gets the currently selected cognito plugin.
   *
   * @return \Drupal\cognito\Plugin\cognito\CognitoFlowInterface
   *   The selected flow plugin.
   */
  public function getSelectedFlow();

}
