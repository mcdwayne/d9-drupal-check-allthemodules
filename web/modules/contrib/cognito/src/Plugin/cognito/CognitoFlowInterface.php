<?php

namespace Drupal\cognito\Plugin\cognito;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * The cognito flow plugin interface.
 */
interface CognitoFlowInterface extends PluginInspectionInterface, PluginWithFormsInterface {

  const NEW_PASSWORD_REQUIRED = 'NEW_PASSWORD_REQUIRED';

  /**
   * Gets the setup instructions.
   *
   * @return string
   *   The setup instructions for Cognito.
   */
  public function getSetupInstructions();

  /**
   * Gets the challenge route if it is configured.
   *
   * @param string $challengeName
   *   The challenge name.
   *
   * @return string|null
   *   The route if it exists otherwise NULL.
   */
  public function getChallengeRoute($challengeName);

}
